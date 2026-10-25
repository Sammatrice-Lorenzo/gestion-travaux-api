<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\ProductInvoiceFile;
use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Enum\ProductInvoiceSourceEnum;
use App\Exceptions\InvoicingPlatformException;
use App\Message\SyncSupplierInvoicesMessage;
use App\MessageHandler\SyncSupplierInvoicesHandler;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Fake\FakeFirebaseNotificationService;
use App\Tests\Support\Fake\FakeInvoicingPlatformClient;
use App\Tests\Support\FunctionalTester;
use LogicException;
use Symfony\Component\Lock\LockFactory;

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
            ['token' => 'device-handler', 'title' => SupplierInvoiceImportNotifier::TITLE, 'body' => SupplierInvoiceImportNotifier::importedBody(1)],
            $this->firebase->sent,
        );
    }

    public function testOwnerIsToldWhenNothingIsNew(FunctionalTester $I): void
    {
        $user = $this->grabUserWithDevice($I, 'device-nothing-new');

        ($this->handler)(new SyncSupplierInvoicesMessage((int) $user->getId()));

        $I->assertContains($this->notification('device-nothing-new', SupplierInvoiceImportNotifier::NOTHING_NEW_BODY), $this->firebase->sent);
    }

    public function testOwnerIsToldWhenSyncIsAlreadyRunning(FunctionalTester $I): void
    {
        $user = $this->grabUserWithDevice($I, 'device-running');

        /** @var LockFactory $lockFactory */
        $lockFactory = $I->grabService(LockFactory::class);
        $runningSync = $lockFactory->createLock('supplier-invoice-sync-' . $user->getId());
        $I->assertTrue($runningSync->acquire());

        try {
            ($this->handler)(new SyncSupplierInvoicesMessage((int) $user->getId()));
        } finally {
            $runningSync->release();
        }

        $I->assertSame([], $this->client->requestedWindows);
        $I->assertContains($this->notification('device-running', SupplierInvoiceImportNotifier::ALREADY_RUNNING_BODY), $this->firebase->sent);
    }

    public function testOwnerIsToldWhenPlatformIsUnavailable(FunctionalTester $I): void
    {
        $user = $this->grabUserWithDevice($I, 'device-unavailable');
        $this->client->failListing(new InvoicingPlatformException('Qonto indisponible', 503));

        // Pas d'exception : le message n'est pas retenté, la synchro planifiée prendra le relais.
        ($this->handler)(new SyncSupplierInvoicesMessage((int) $user->getId()));

        $I->assertContains($this->notification('device-unavailable', SupplierInvoiceImportNotifier::PLATFORM_UNAVAILABLE_BODY), $this->firebase->sent);
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

    private function grabUserWithDevice(FunctionalTester $I, string $token): User
    {
        $user = $this->grabUser($I, UserFixturesEnum::DEFAULT_USER->value);
        $I->haveInRepository(TokenNotificationPush::class, ['token' => $token, 'userAgent' => "test-{$token}", 'user' => $user]);

        return $user;
    }

    /**
     * @return array{token: string, title: string, body: string}
     */
    private function notification(string $token, string $body): array
    {
        return ['token' => $token, 'title' => SupplierInvoiceImportNotifier::TITLE, 'body' => $body];
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
