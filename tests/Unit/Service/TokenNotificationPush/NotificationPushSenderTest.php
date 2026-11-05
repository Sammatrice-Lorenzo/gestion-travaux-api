<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\TokenNotificationPush;

use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;
use App\Repository\TokenNotificationPushRepository;
use App\Service\TokenNotificationPush\NotificationPushSender;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final class NotificationPushSenderTest extends Unit
{
    protected UnitTester $tester;

    private FirebaseNotificationServiceInterface&MockObject $firebase;

    private TokenNotificationPushRepository $tokenRepository;

    private NotificationPushSender $sender;

    private User $user;

    protected function _before(): void
    {
        $this->firebase = $this->createMock(FirebaseNotificationServiceInterface::class);

        /** @var TokenNotificationPushRepository $tokenRepository */
        $tokenRepository = $this->tester->grabService(TokenNotificationPushRepository::class);
        $this->tokenRepository = $tokenRepository;
        $this->sender = new NotificationPushSender($this->firebase, $tokenRepository, new NullLogger());

        /** @var User $user */
        $user = $this->tester->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
    }

    public function testUnregisteredTokenIsDeletedAndOthersStillNotified(): void
    {
        $devices = $this->haveDevices('sender-dead', 'sender-alive');
        $this->firebase->method('sendNotification')->willReturnCallback(
            static fn (string $token): array => 'sender-dead' === $token
                ? self::firebaseError(404, 'NOT_FOUND', 'UNREGISTERED')
                : ['name' => 'projects/x/messages/1'],
        );

        $this->assertSame(1, $this->sender->send($devices, 'Titre', 'Message'));

        $this->tester->dontSeeInRepository(TokenNotificationPush::class, ['token' => 'sender-dead']);
        $this->tester->seeInRepository(TokenNotificationPush::class, ['token' => 'sender-alive']);
    }

    public function testOtherFirebaseErrorKeepsToken(): void
    {
        $devices = $this->haveDevices('sender-invalid-request');
        $this->firebase->method('sendNotification')->willReturn(self::firebaseError(400, 'INVALID_ARGUMENT', 'INVALID_ARGUMENT'));

        $this->assertSame(0, $this->sender->send($devices, 'Titre', 'Message'));

        $this->tester->seeInRepository(TokenNotificationPush::class, ['token' => 'sender-invalid-request']);
    }

    public function testFirebaseUnavailableKeepsToken(): void
    {
        $devices = $this->haveDevices('sender-network-error');
        $this->firebase->method('sendNotification')->willThrowException(new RuntimeException('Firebase indisponible'));

        $this->assertSame(0, $this->sender->send($devices, 'Titre', 'Message'));

        $this->tester->seeInRepository(TokenNotificationPush::class, ['token' => 'sender-network-error']);
    }

    public function testDeletionFailureDoesNotStopOtherDevices(): void
    {
        $devices = $this->haveDevices('sender-dead-undeletable', 'sender-reachable');
        $this->firebase->method('sendNotification')->willReturnCallback(
            static fn (string $token): array => 'sender-dead-undeletable' === $token
                ? self::firebaseError(404, 'NOT_FOUND', 'UNREGISTERED')
                : ['name' => 'projects/x/messages/1'],
        );

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willThrowException(new RuntimeException('Base indisponible'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Notification push : suppression du token impossible.');

        $sender = new NotificationPushSender($this->firebase, new TokenNotificationPushRepository($registry), $logger);

        $this->assertSame(1, $sender->send($devices, 'Titre', 'Message'));
    }

    public function testDeleteByTokenLeavesOtherTokens(): void
    {
        $this->haveDevices('sender-repo-deleted', 'sender-repo-kept');

        $this->tokenRepository->deleteByToken('sender-repo-deleted');

        $this->tester->dontSeeInRepository(TokenNotificationPush::class, ['token' => 'sender-repo-deleted']);
        $this->tester->seeInRepository(TokenNotificationPush::class, ['token' => 'sender-repo-kept']);
    }

    /**
     * @return list<TokenNotificationPush>
     */
    private function haveDevices(string ...$tokens): array
    {
        $devices = [];
        foreach ($tokens as $token) {
            $this->tester->haveInRepository(TokenNotificationPush::class, ['token' => $token, 'userAgent' => "test-{$token}", 'user' => $this->user]);
            /** @var TokenNotificationPush $device */
            $device = $this->tester->grabEntityFromRepository(TokenNotificationPush::class, ['token' => $token]);
            $devices[] = $device;
        }

        return $devices;
    }

    /**
     * @return array<string, mixed>
     */
    private static function firebaseError(int $code, string $status, string $errorCode): array
    {
        return [
            'error' => [
                'code' => $code,
                'status' => $status,
                'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => $errorCode]],
            ],
        ];
    }
}
