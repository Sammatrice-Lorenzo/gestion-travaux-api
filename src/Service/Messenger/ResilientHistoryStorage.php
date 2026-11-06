<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\ProcessedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Messenger\Envelope;
use Throwable;
use Zenstruck\Collection;
use Zenstruck\Messenger\Monitor\History\Model\ProcessedMessage as BaseProcessedMessage;
use Zenstruck\Messenger\Monitor\History\Model\Results;
use Zenstruck\Messenger\Monitor\History\Specification;
use Zenstruck\Messenger\Monitor\History\Storage;

/**
 * L'historique est écrit par un listener du worker, avant l'acquittement du message : une erreur ici arrêterait le
 * worker et ferait retraiter le message. Le monitoring ne doit donc jamais faire échouer le traitement.
 */
#[AsDecorator('zenstruck_messenger_monitor.history.storage')]
final readonly class ResilientHistoryStorage implements Storage
{
    public function __construct(
        #[AutowireDecorated]
        private Storage $storage,
        private ManagerRegistry $doctrine,
        private LoggerInterface $logger,
    ) {}

    public function save(Envelope $envelope, Results $results, ?Throwable $exception = null): void
    {
        try {
            // Un flush en échec dans le handler ferme l'EntityManager, qui n'est réinitialisé qu'après ce listener.
            $manager = $this->doctrine->getManagerForClass(ProcessedMessage::class);
            if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
                $this->doctrine->resetManager();
            }

            $this->storage->save($envelope, $results, $exception);
        } catch (Throwable $saveException) {
            $this->logger->error('Historique Messenger non enregistré.', ['message' => $envelope->getMessage()::class, 'exception' => $saveException]);
        }
    }

    public function find(mixed $id): ?BaseProcessedMessage
    {
        return $this->storage->find($id);
    }

    public function filter(Specification $specification): Collection
    {
        return $this->storage->filter($specification);
    }

    public function purge(Specification $specification): int
    {
        return $this->storage->purge($specification);
    }

    public function delete(mixed $id): void
    {
        $this->storage->delete($id);
    }

    public function averageWaitTime(Specification $specification): ?int
    {
        return $this->storage->averageWaitTime($specification);
    }

    public function averageHandlingTime(Specification $specification): ?int
    {
        return $this->storage->averageHandlingTime($specification);
    }

    public function count(Specification $specification): int
    {
        return $this->storage->count($specification);
    }

    public function perMessageTypeMetrics(Specification $specification): Collection
    {
        return $this->storage->perMessageTypeMetrics($specification);
    }

    public function availableMessageTypes(Specification $specification): Collection
    {
        return $this->storage->availableMessageTypes($specification);
    }
}
