<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SchedulerHeartbeatSubscriber;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Scheduler\Event\PostRunEvent;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;

final class SchedulerHeartbeatSubscriberTest extends Unit
{
    private const string HEARTBEAT_URL = 'https://glitchtip.test/api/0/organizations/perso/heartbeat_check/uuid/';

    private const string COMMAND = 'app:qonto:sync-supplier-invoices';

    public function testPingsHeartbeatAfterSuccessfulCommand(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requests): MockResponse {
            $requests[] = [$method, $url];

            return new MockResponse('', ['http_code' => 200]);
        });

        $subscriber = new SchedulerHeartbeatSubscriber($httpClient, new NullLogger(), [self::COMMAND => self::HEARTBEAT_URL]);
        $subscriber->onPostRun($this->postRunEvent(new RunCommandMessage(self::COMMAND . ' --from=2026-01-01')));

        $this->assertSame([['POST', self::HEARTBEAT_URL]], $requests);
    }

    public function testNoHeartbeatWhenUrlIsEmptyOrTaskUnknown(): void
    {
        $httpClient = new MockHttpClient(function (): never {
            $this->fail('Aucun heartbeat attendu.');
        });

        $subscriber = new SchedulerHeartbeatSubscriber($httpClient, new NullLogger(), [self::COMMAND => '']);
        $subscriber->onPostRun($this->postRunEvent(new RunCommandMessage(self::COMMAND)));
        $subscriber->onPostRun($this->postRunEvent(new RunCommandMessage('app:other-command')));

        $this->assertSame(0, $httpClient->getRequestsCount());
    }

    public function testHeartbeatFailureIsLoggedWithoutBreakingTheTask(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Heartbeat impossible à envoyer.');

        $httpClient = new MockHttpClient(new MockResponse('', ['error' => 'Connexion refusée']));
        $subscriber = new SchedulerHeartbeatSubscriber($httpClient, $logger, [self::COMMAND => self::HEARTBEAT_URL]);

        $subscriber->onPostRun($this->postRunEvent(new RunCommandMessage(self::COMMAND)));
    }

    public function testRejectedHeartbeatIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Heartbeat refusé.');

        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $subscriber = new SchedulerHeartbeatSubscriber($httpClient, $logger, [self::COMMAND => self::HEARTBEAT_URL]);

        $subscriber->onPostRun($this->postRunEvent(new RunCommandMessage(self::COMMAND)));
    }

    public function testTaskName(): void
    {
        $this->assertSame(self::COMMAND, SchedulerHeartbeatSubscriber::taskName(new RunCommandMessage('  ' . self::COMMAND . ' --from=2026-01-01')));
        $this->assertSame(stdClass::class, SchedulerHeartbeatSubscriber::taskName(new stdClass()));
    }

    public function testSubscribesToPostRunEvent(): void
    {
        $this->assertArrayHasKey(PostRunEvent::class, SchedulerHeartbeatSubscriber::getSubscribedEvents());
    }

    private function postRunEvent(object $message): PostRunEvent
    {
        $context = new MessageContext('default', 'task-id', $this->createMock(TriggerInterface::class), new DateTimeImmutable());

        return new PostRunEvent($this->createMock(ScheduleProviderInterface::class), $context, $message);
    }
}
