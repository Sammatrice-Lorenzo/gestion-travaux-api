<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\ProductInvoiceFile;
use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Enum\ProductInvoiceSourceEnum;
use App\Message\SyncSupplierInvoicesMessage;
use App\MessageHandler\SyncSupplierInvoicesHandler;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Fake\FakeFirebaseNotificationService;
use App\Tests\Support\Fake\FakeInvoicingPlatformClient;
use App\Tests\Support\FunctionalTester;
use LogicException;

final class SyncSupplierInvoicesHandlerCest
{
    private FakeInvoicingPlatformClient $client;

    private FakeFirebaseNotificationService $firebase;

    private SyncSupplierInvoicesHandler $handler;

    public function _before(FunctionalTester $I): void
    {
        /** @var FakeInvoicingPlatformClient $client */
        $client = $I->grabService(FakeInvoicingPlatformClient::class);
        $this->client = $client;
        $this->client->reset();

        /** @var FakeFirebaseNotificationService $firebase */
        $firebase = $I->grabService(FakeFirebaseNotificationService::class);
        $this->firebase = $firebase;
        $this->firebase->sent = [];

        /** @var SyncSupplierInvoicesHandler $handler */
        $handler = $I->grabService(SyncSupplierInvoicesHandler::class);
        $this->handler = $handler;
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

    public function testOwnerSyncImportsInvoicesAndNotifies(FunctionalTester $I): void
    {
        $user = $this->grabUser($I, UserFixturesEnum::DEFAULT_USER->value);
        $I->haveInRepository(TokenNotificationPush::class, ['token' => 'device-handler', 'userAgent' => 'test-handler', 'user' => $user]);
        $this->client->addInvoice(
            FakeInvoicingPlatformClient::invoice('handler-pdf', supplierName: 'Fournisseur Handler'),
            content: (string) file_get_contents(codecept_data_dir('InvoiceTemplate.pdf')),
        );

        ($this->handler)(new SyncSupplierInvoicesMessage((int) $user->getId()));

        $I->seeInRepository(ProductInvoiceFile::class, ['user' => $user, 'qontoId' => 'handler-pdf']);
        $I->assertContains(
            ['token' => 'device-handler', 'title' => SupplierInvoiceImportNotifier::TITLE, 'body' => SupplierInvoiceImportNotifier::body(1)],
            $this->firebase->sent,
        );
    }

    public function testOtherUserCannotImportOwnerInvoices(FunctionalTester $I): void
    {
        $otherUser = $this->grabOtherUser($I);
        $this->client->addInvoice(
            FakeInvoicingPlatformClient::invoice('handler-other'),
            content: (string) file_get_contents(codecept_data_dir('InvoiceTemplate.pdf')),
        );

        ($this->handler)(new SyncSupplierInvoicesMessage((int) $otherUser->getId()));

        $I->dontSeeInRepository(ProductInvoiceFile::class, ['qontoId' => 'handler-other']);
        $I->assertSame([], $this->client->requestedWindows);
        $I->assertSame([], $this->firebase->sent);
    }

    public function testUnknownUserIsIgnored(FunctionalTester $I): void
    {
        ($this->handler)(new SyncSupplierInvoicesMessage(PHP_INT_MAX));

        $I->assertSame([], $this->client->requestedWindows);
    }

    private function grabUser(FunctionalTester $I, string $email): User
    {
        $user = $I->grabEntityFromRepository(User::class, ['email' => $email]);
        $I->assertInstanceOf(User::class, $user);

        return $user;
    }

    private function grabOtherUser(FunctionalTester $I): User
    {
        /** @var User[] $users */
        $users = $I->grabEntitiesFromRepository(User::class);
        foreach ($users as $user) {
            if (UserFixturesEnum::DEFAULT_USER->value !== $user->getEmail()) {
                return $user;
            }
        }

        throw new LogicException('Les fixtures doivent contenir un second utilisateur.');
    }
}
