<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\ProductInvoiceFile;
use App\Entity\User;
use App\Enum\ProductInvoiceSourceEnum;
use App\Exceptions\InvoicingPlatformException;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Fake\FakeInvoicingPlatformClient;
use App\Tests\Support\FunctionalTester;
use Symfony\Component\Console\Command\Command;

final class QontoSyncSupplierInvoicesCommandCest
{
    private const string COMMAND = 'app:qonto:sync-supplier-invoices';

    private FakeInvoicingPlatformClient $client;

    public function _before(FunctionalTester $I): void
    {
        /** @var FakeInvoicingPlatformClient $client */
        $client = $I->grabService(FakeInvoicingPlatformClient::class);
        $this->client = $client;
        $this->client->reset();
    }

    public function _after(FunctionalTester $I): void
    {
        /** @var string $projectDir */
        $projectDir = $I->grabParameter('kernel.project_dir');

        /** @var ProductInvoiceFile[] $productInvoiceFiles */
        $productInvoiceFiles = $I->grabEntitiesFromRepository(ProductInvoiceFile::class, ['source' => ProductInvoiceSourceEnum::QONTO]);
        foreach ($productInvoiceFiles as $productInvoiceFile) {
            $path = "{$projectDir}/public/products-invoice/{$productInvoiceFile->getPath()}";
            if (null !== $productInvoiceFile->getPath() && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testImportsInvoicesWithoutDuplicates(FunctionalTester $I): void
    {
        $pdf = (string) file_get_contents(codecept_data_dir('InvoiceTemplate.pdf'));
        $this->client
            ->addInvoice(FakeInvoicingPlatformClient::invoice('cmd-pdf', supplierName: 'Fournisseur Commande'), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('cmd-rejected', status: 'rejected'), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('cmd-heic'), fileName: 'photo.heic', contentType: 'image/heic', content: random_bytes(64))
        ;

        $output = $I->runSymfonyConsoleCommand(self::COMMAND, ['--from' => '2026-01-01']);

        $I->assertMatchesRegularExpression('/^\s*1\s+0\s+2\s+0\s*$/m', $output);
        $I->assertStringContainsString('F-cmd-heic', $output);
        $I->assertStringContainsString('Synchro terminée', $output);

        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $I->seeInRepository(ProductInvoiceFile::class, ['user' => $user, 'qontoId' => 'cmd-pdf', 'source' => ProductInvoiceSourceEnum::QONTO]);
        $I->dontSeeInRepository(ProductInvoiceFile::class, ['qontoId' => 'cmd-rejected']);

        $secondOutput = $I->runSymfonyConsoleCommand(self::COMMAND, ['--from' => '2026-01-01']);

        $I->assertMatchesRegularExpression('/^\s*0\s+0\s+3\s+0\s*$/m', $secondOutput);
        $I->assertCount(1, $I->grabEntitiesFromRepository(ProductInvoiceFile::class, ['qontoId' => 'cmd-pdf']));
    }

    public function testInvoiceErrorReturnsFailure(FunctionalTester $I): void
    {
        $this->client->addInvoice(FakeInvoicingPlatformClient::invoice('cmd-broken'));

        $output = $I->runSymfonyConsoleCommand(self::COMMAND, ['--from' => '2026-01-01'], expectedExitCode: Command::FAILURE);

        $I->assertStringContainsString('F-cmd-broken', $output);
    }

    public function testListingFailureReturnsFailure(FunctionalTester $I): void
    {
        $this->client->failListing(new InvoicingPlatformException('Qonto indisponible', 503));

        $output = $I->runSymfonyConsoleCommand(self::COMMAND, expectedExitCode: Command::FAILURE);

        $I->assertStringContainsString('Qonto indisponible', $output);
    }

    public function testInvalidFromOption(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand(self::COMMAND, ['--from' => '01/01/2026'], expectedExitCode: Command::INVALID);

        $I->assertStringContainsString('AAAA-MM-JJ', $output);
    }
}
