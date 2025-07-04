<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use Faker\Factory;
use App\Entity\User;
use App\Tests\Enum\UserFixturesEnum;
use App\Entity\TokenNotificationPush;
use App\Tests\Support\FunctionalTester;
use Symfony\Component\Console\Command\Command;

final class NotificationPushCommandCest
{
    public function testCommandMissingArguments(FunctionalTester $I): void
    {
        $response = $I->runSymfonyConsoleCommand(command: 'app:send-notification-push', expectedExitCode: Command::INVALID);
        $I->assertStringContainsString(trim($response), '[ERROR] Arguments : "title" and "body" are required !');
    }

    public function testCommandSuccess(FunctionalTester $I): void
    {
        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->generateTokenPushNotification($I, $user);

        $response = $I->runSymfonyConsoleCommand('app:send-notification-push', [
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);

        $this->assertNoteCommand($I, $response);
    }

    private function assertNoteCommand(FunctionalTester $I, string $response): void
    {
        $text = str_replace(["\r\n", "\r"], "\n", $response);
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $lines = array_values($lines);

        $responsesToAssert = $this->getResponseCommandToAssert();
        foreach ($lines as $key => $text) {
            $I->assertStringContainsString($text, $responsesToAssert[$key]);
        }
    }

    /**
     * @return string[]
     */
    private function getResponseCommandToAssert(): array
    {
        return [
            '! [NOTE] You passed an argument: Test Title',
            '! [NOTE] You passed an argument: Test Body',
            '[OK] Notifications push sent 1 out of 1',
        ];
    }

    private function generateTokenPushNotification(FunctionalTester $I, User $user): void
    {
        $faker = Factory::create('fr_FR');
        $tokenNotificatioPush = (new TokenNotificationPush())
            ->setToken($faker->linuxPlatformToken())
            ->setUserAgent($faker->userAgent())
            ->setUser($user)
        ;

        $em = $I->grabEntityManagerInterface();
        $em->persist($tokenNotificatioPush);
        $em->flush();
    }
}
