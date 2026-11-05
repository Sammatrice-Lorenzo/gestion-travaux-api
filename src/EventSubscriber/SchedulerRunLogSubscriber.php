<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\RunCommandFailedException;
use Symfony\Component\Console\Messenger\RunCommandContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\WrappedExceptionsInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Throwable;

/**
 * Journalise chaque exécution d'une tâche planifiée, avec le rapport de la commande : le worker la lance dans une
 * sortie en mémoire, qui serait sinon perdue, y compris le motif d'un échec.
 */
final readonly class SchedulerRunLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $schedulerLogger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'onMessageHandled',
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $envelope = $event->getEnvelope();
        if (null === $envelope->last(ScheduledStamp::class)) {
            return;
        }

        $result = $envelope->last(HandledStamp::class)?->getResult();

        $this->schedulerLogger->info('Tâche planifiée terminée.', [
            'task' => SchedulerHeartbeatSubscriber::taskName($envelope->getMessage()),
            ...($result instanceof RunCommandContext ? self::commandContext($result) : []),
        ]);
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        if (null === $envelope->last(ScheduledStamp::class)) {
            return;
        }

        $throwable = $event->getThrowable();
        $commandFailure = self::findCommandFailure($throwable);

        $this->schedulerLogger->error('Tâche planifiée en échec.', [
            'task' => SchedulerHeartbeatSubscriber::taskName($envelope->getMessage()),
            'error' => $throwable->getMessage(),
            ...(null !== $commandFailure ? self::commandContext($commandFailure->context) : []),
        ]);
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private static function commandContext(RunCommandContext $context): array
    {
        return ['exitCode' => $context->exitCode, 'output' => trim($context->output)];
    }

    private static function findCommandFailure(Throwable $throwable): ?RunCommandFailedException
    {
        if ($throwable instanceof RunCommandFailedException) {
            return $throwable;
        }

        if (!$throwable instanceof WrappedExceptionsInterface) {
            return null;
        }

        // Le tableau est indexé par nom de handler.
        return array_values($throwable->getWrappedExceptions(RunCommandFailedException::class, true))[0] ?? null;
    }
}
