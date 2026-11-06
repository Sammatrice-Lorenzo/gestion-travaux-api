<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SchedulerRunLogSubscriber;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Exception\RunCommandFailedException;
use Symfony\Component\Console\Messenger\RunCommandContext;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;

final class SchedulerRunLogSubscriberTest extends Unit
{
    private const string COMMAND = 'app:qonto:sync-supplier-invoices';

    private const string RECEIVER = 'scheduler_default';

    public function testLogsCommandReportAfterSuccessfulRun(): void
    {
        $message = new RunCommandMessage(self::COMMAND . ' --from=2026-01-01');
        $envelope = $this->scheduledEnvelope($message)->with(new HandledStamp(new RunCommandContext($message, 0, "  Rapport\n"), 'handler'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Tâche planifiée terminée.', [
            'task' => self::COMMAND,
            'exitCode' => 0,
            'output' => 'Rapport',
        ]);

        (new SchedulerRunLogSubscriber($logger))->onMessageHandled(new WorkerMessageHandledEvent($envelope, self::RECEIVER));
    }

    public function testLogsOnlyTaskNameForScheduledMessageWithoutCommandResult(): void
    {
        $envelope = $this->scheduledEnvelope(new stdClass())->with(new HandledStamp(null, 'handler'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Tâche planifiée terminée.', ['task' => stdClass::class]);

        (new SchedulerRunLogSubscriber($logger))->onMessageHandled(new WorkerMessageHandledEvent($envelope, self::RECEIVER));
    }

    public function testLogsFailureReasonFromCommandOutput(): void
    {
        $message = new RunCommandMessage(self::COMMAND);
        $envelope = $this->scheduledEnvelope($message);
        $failure = new RunCommandFailedException('Command exited with code "1".', new RunCommandContext($message, 1, 'Synchro interrompue : Qonto indisponible.'));
        $exception = new HandlerFailedException($envelope, ['handler' => $failure]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Tâche planifiée en échec.', [
            'task' => self::COMMAND,
            'error' => $exception->getMessage(),
            'exitCode' => 1,
            'output' => 'Synchro interrompue : Qonto indisponible.',
        ]);

        (new SchedulerRunLogSubscriber($logger))->onMessageFailed(new WorkerMessageFailedEvent($envelope, self::RECEIVER, $exception));
    }

    public function testLogsFailureWithoutCommandContext(): void
    {
        $envelope = $this->scheduledEnvelope(new stdClass());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Tâche planifiée en échec.', ['task' => stdClass::class, 'error' => 'Panne']);

        (new SchedulerRunLogSubscriber($logger))->onMessageFailed(new WorkerMessageFailedEvent($envelope, self::RECEIVER, new RuntimeException('Panne')));
    }

    public function testIgnoresMessagesThatAreNotScheduled(): void
    {
        $envelope = new Envelope(new stdClass());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method($this->anything());

        $subscriber = new SchedulerRunLogSubscriber($logger);
        $subscriber->onMessageHandled(new WorkerMessageHandledEvent($envelope, 'async'));
        $subscriber->onMessageFailed(new WorkerMessageFailedEvent($envelope, 'async', new RuntimeException('Panne')));
    }

    private function scheduledEnvelope(object $message): Envelope
    {
        $context = new MessageContext('default', 'task-id', $this->createMock(TriggerInterface::class), new DateTimeImmutable());

        return new Envelope($message, [new ScheduledStamp($context)]);
    }
}
