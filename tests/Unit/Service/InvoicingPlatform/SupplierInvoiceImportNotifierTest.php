<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use RuntimeException;

final class SupplierInvoiceImportNotifierTest extends Unit
{
    private FirebaseNotificationServiceInterface&MockObject $firebase;

    /**
     * @var EntityRepository<TokenNotificationPush>&MockObject
     */
    private EntityRepository&MockObject $tokenRepository;

    private SupplierInvoiceImportNotifier $notifier;

    private User $user;

    protected function _before(): void
    {
        $this->firebase = $this->createMock(FirebaseNotificationServiceInterface::class);
        $this->tokenRepository = $this->createMock(EntityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(TokenNotificationPush::class)->willReturn($this->tokenRepository);
        $this->notifier = new SupplierInvoiceImportNotifier($this->firebase, $entityManager, new NullLogger());
        $this->user = new User();
    }

    public function testNotifiesEveryDeviceOfTheUser(): void
    {
        $this->tokenRepository->method('findBy')->with(['user' => $this->user])->willReturn([$this->token('phone'), $this->token('laptop')]);
        $this->firebase->expects($this->exactly(2))
            ->method('sendNotification')
            ->with($this->anything(), SupplierInvoiceImportNotifier::TITLE, '3 nouvelles factures fournisseurs importées depuis Qonto')
            ->willReturn(['name' => 'projects/x/messages/1'])
        ;

        $this->assertSame(2, $this->notifier->notify($this->user, $this->syncResult(created: 3)));
    }

    public function testNothingSentWithoutNewInvoice(): void
    {
        $this->tokenRepository->expects($this->never())->method('findBy');
        $this->firebase->expects($this->never())->method('sendNotification');

        $this->assertSame(0, $this->notifier->notify($this->user, $this->syncResult(created: 0)));
    }

    public function testUnreachableDeviceDoesNotStopOthers(): void
    {
        $this->tokenRepository->method('findBy')->willReturn([$this->token('expired'), $this->token('phone')]);
        $this->firebase->method('sendNotification')->willReturnCallback(
            static fn (string $token): array => 'expired' === $token ? throw new RuntimeException('UNREGISTERED') : ['name' => 'ok'],
        );

        $this->assertSame(1, $this->notifier->notify($this->user, $this->syncResult(created: 1)));
    }

    public function testBodyIsSingularForOneInvoice(): void
    {
        $this->assertSame('1 nouvelle facture fournisseur importée depuis Qonto', SupplierInvoiceImportNotifier::body(1));
        $this->assertSame('2 nouvelles factures fournisseurs importées depuis Qonto', SupplierInvoiceImportNotifier::body(2));
    }

    private function syncResult(int $created): SupplierInvoiceSyncResult
    {
        $result = new SupplierInvoiceSyncResult();
        $result->created = $created;

        return $result;
    }

    private function token(string $token): TokenNotificationPush
    {
        return (new TokenNotificationPush())->setToken($token)->setUserAgent('test')->setUser($this->user);
    }
}
