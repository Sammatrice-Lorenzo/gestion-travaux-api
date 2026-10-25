<?php

declare(strict_types=1);

namespace App\Tests\Unit\Processor;

use ApiPlatform\Metadata\Post;
use App\Dto\InvoicingPlatform\SupplierInvoiceSyncStatus;
use App\Entity\User;
use App\Message\SyncSupplierInvoicesMessage;
use App\Processor\SupplierInvoiceSyncProcessor;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

final class SupplierInvoiceSyncProcessorTest extends Unit
{
    protected UnitTester $tester;

    private UserRepository $userRepository;

    protected function _before(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->tester->grabService(UserRepository::class);
        $this->userRepository = $userRepository;
    }

    public function testOwnerRequestIsQueuedWithDeduplication(): void
    {
        $owner = $this->defaultUser();
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->willReturnCallback(
            static function (object $message, array $stamps) use (&$dispatched): Envelope {
                $dispatched = [$message, $stamps];

                return new Envelope($message, $stamps);
            },
        );

        $status = $this->processor($bus, $owner)->process(null, new Post());

        $this->assertSame(SupplierInvoiceSyncStatus::STARTED, $status->message);
        [$message, $stamps] = $dispatched;
        $this->assertInstanceOf(SyncSupplierInvoicesMessage::class, $message);
        $this->assertSame($owner->getId(), $message->userId);
        $this->assertInstanceOf(DeduplicateStamp::class, $stamps[0]);
        $this->assertSame("supplier-invoice-sync-request-{$owner->getId()}", (string) $stamps[0]->getKey());
    }

    public function testOtherUserIsDenied(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->expectException(AccessDeniedHttpException::class);

        $this->processor($bus, $this->otherUser())->process(null, new Post());
    }

    private function processor(MessageBusInterface $bus, User $currentUser): SupplierInvoiceSyncProcessor
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($currentUser);

        return new SupplierInvoiceSyncProcessor(
            $bus,
            new InvoicingPlatformAccountOwner($this->userRepository, UserFixturesEnum::DEFAULT_USER->value),
            $security,
        );
    }

    private function defaultUser(): User
    {
        return $this->userRepository->findOneBy(['email' => UserFixturesEnum::DEFAULT_USER->value])
            ?? throw new LogicException('Utilisateur des fixtures introuvable.');
    }

    private function otherUser(): User
    {
        foreach ($this->userRepository->findAll() as $user) {
            if (UserFixturesEnum::DEFAULT_USER->value !== $user->getEmail()) {
                return $user;
            }
        }

        throw new LogicException('Les fixtures doivent contenir un second utilisateur.');
    }
}
