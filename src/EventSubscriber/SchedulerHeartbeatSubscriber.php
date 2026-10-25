<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Scheduler\Event\PostRunEvent;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Après chaque exécution réussie d'une tâche planifiée, prévient son moniteur heartbeat (GlitchTip).
 * Une tâche en échec n'envoie rien : l'absence de heartbeat déclenche l'alerte, comme un worker arrêté.
 */
final readonly class SchedulerHeartbeatSubscriber implements EventSubscriberInterface
{
    private const int TIMEOUT_SECONDS = 10;

    /**
     * @param array<string, string> $heartbeatUrls URL par tâche (nom de commande ou classe du message)
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(param: 'scheduler_heartbeat_urls')]
        private array $heartbeatUrls,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [PostRunEvent::class => 'onPostRun'];
    }

    public function onPostRun(PostRunEvent $event): void
    {
        $taskName = self::taskName($event->getMessage());
        $url = trim($this->heartbeatUrls[$taskName] ?? '');
        if ('' === $url) {
            return;
        }

        // Le heartbeat ne doit jamais faire échouer la tâche : une erreur est seulement journalisée.
        try {
            $statusCode = $this->httpClient->request('POST', $url, ['timeout' => self::TIMEOUT_SECONDS])->getStatusCode();
            if ($statusCode >= 300) {
                $this->logger->warning('Heartbeat refusé.', ['task' => $taskName, 'status' => $statusCode]);
            }
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Heartbeat impossible à envoyer.', ['task' => $taskName, 'exception' => $exception]);
        }
    }

    /**
     * « app:qonto:sync-supplier-invoices --from=... » devient « app:qonto:sync-supplier-invoices ».
     */
    public static function taskName(object $message): string
    {
        if ($message instanceof RunCommandMessage) {
            return explode(' ', trim($message->input), 2)[0];
        }

        return $message::class;
    }
}
