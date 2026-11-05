<?php

declare(strict_types=1);

namespace App;

use DateTimeZone;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Planning « default », exécuté par le worker `messenger:consume scheduler_default`. Les tâches sont déclarées
 * directement sur les commandes avec #[AsCronTask] ; ce provider règle leur comportement et ajoute les tâches
 * de commandes tierces, qui ne peuvent pas porter l'attribut.
 */
#[AsSchedule]
final readonly class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private LockFactory $lockFactory,
    ) {}

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            // Historique du tableau de bord Messenger conservé un mois.
            ->add(RecurringMessage::cron('0 3 * * *', new RunCommandMessage('messenger:monitor:purge'), new DateTimeZone('Europe/Paris')))
            // Une tâche manquée (worker redémarré) est rattrapée, une seule fois.
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            // Jamais deux workers sur le même planning en même temps.
            ->lock($this->lockFactory->createLock('scheduler_default'))
        ;
    }
}
