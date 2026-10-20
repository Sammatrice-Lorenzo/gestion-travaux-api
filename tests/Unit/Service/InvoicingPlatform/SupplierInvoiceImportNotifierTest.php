<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;
use App\Repository\TokenNotificationPushRepository;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use App\Service\TokenNotificationPush\NotificationPushSender;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use RuntimeException;

final class SupplierInvoiceImportNotifierTest extends Unit
{
    protected UnitTester $tester;

    private FirebaseNotificationServiceInterface&MockObject $firebase;

    private SupplierInvoiceImportNotifier $notifier;

    private User $user;

    protected function _before(): void
    {
        $this->firebase = $this->createMock(FirebaseNotificationServiceInterface::class);

        /** @var TokenNotificationPushRepository $tokenRepository */
        $tokenRepository = $this->tester->grabService(TokenNotificationPushRepository::class);
        $this->notifier = new SupplierInvoiceImportNotifier(new NotificationPushSender($this->firebase, new NullLogger()), $tokenRepository, new NullLogger());

        /** @var User $user */
        $user = $this->tester->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
    }

    public function testNotifiesEveryDeviceOfTheUser(): void
    {
        $this->haveDevices('notifier-phone', 'notifier-laptop');
        $sentTo = [];
        $this->firebase->method('sendNotification')->willReturnCallback(
            static function (string $token, string $title, string $body) use (&$sentTo): array {
                $sentTo[] = [$token, $title, $body];

                return ['name' => 'projects/x/messages/1'];
            },
        );

        $this->notifier->notifyImported($this->user, $this->syncResult(created: 3));

        $body = '3 nouvelles factures fournisseurs importées depuis Qonto';
        $this->assertContains(['notifier-phone', SupplierInvoiceImportNotifier::TITLE, $body], $sentTo);
        $this->assertContains(['notifier-laptop', SupplierInvoiceImportNotifier::TITLE, $body], $sentTo);
    }

    public function testScheduledSyncSendsNothingWithoutNewInvoice(): void
    {
        $this->haveDevices('notifier-silent');
        $this->firebase->expects($this->never())->method('sendNotification');

        $this->assertSame(0, $this->notifier->notifyImported($this->user, $this->syncResult(created: 0)));
    }

    public function testRequestedSyncAlwaysReportsItsOutcome(): void
    {
        $this->haveDevices('notifier-outcome');
        $bodies = [];
        $this->firebase->method('sendNotification')->willReturnCallback(
            static function (string $token, string $title, string $body) use (&$bodies): array {
                $bodies[$token] = $body;

                return ['name' => 'ok'];
            },
        );

        $this->notifier->notifyRequestedSync($this->user, $this->syncResult(created: 0));

        $this->assertSame(SupplierInvoiceImportNotifier::NOTHING_NEW_BODY, $bodies['notifier-outcome']);
    }

    public function testUnreachableDeviceDoesNotStopOthers(): void
    {
        $this->haveDevices('notifier-expired', 'notifier-reachable');
        $reached = [];
        $this->firebase->method('sendNotification')->willReturnCallback(
            static function (string $token) use (&$reached): array {
                if ('notifier-expired' === $token) {
                    throw new RuntimeException('UNREGISTERED');
                }

                $reached[] = $token;

                return ['name' => 'ok'];
            },
        );

        $this->notifier->notifyImported($this->user, $this->syncResult(created: 1));

        $this->assertContains('notifier-reachable', $reached);
    }

    public function testNeverThrows(): void
    {
        $this->haveDevices('notifier-broken');
        $this->firebase->method('sendNotification')->willThrowException(new \LogicException('Configuration Firebase invalide'));

        $this->assertSame(0, $this->notifier->notifyImported($this->user, $this->syncResult(created: 2)));
    }

    public function testBodies(): void
    {
        $this->assertSame('1 nouvelle facture fournisseur importée depuis Qonto', SupplierInvoiceImportNotifier::importedBody(1));
        $this->assertSame('2 nouvelles factures fournisseurs importées depuis Qonto', SupplierInvoiceImportNotifier::importedBody(2));

        $incomplete = $this->syncResult(created: 2);
        $incomplete->errors = ['F-1 : Qonto indisponible'];
        $incomplete->failures = ['F-2 : disque plein'];
        $this->assertSame('Synchronisation Qonto incomplète : 2 importée(s), 2 en erreur', SupplierInvoiceImportNotifier::resultBody($incomplete));
    }

    private function haveDevices(string ...$tokens): void
    {
        foreach ($tokens as $token) {
            $this->tester->haveInRepository(TokenNotificationPush::class, ['token' => $token, 'userAgent' => "test-{$token}", 'user' => $this->user]);
        }
    }

    private function syncResult(int $created): SupplierInvoiceSyncResult
    {
        $result = new SupplierInvoiceSyncResult();
        $result->created = $created;

        return $result;
    }
}
