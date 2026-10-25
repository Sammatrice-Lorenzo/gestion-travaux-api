<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Planning « default », exécuté par le worker `messenger:consume scheduler_default`. Les tâches sont déclarées
 * directement sur les commandes avec #[AsCronTask] ; ce provider n'en règle que le comportement.
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
            // Une tâche manquée (worker redémarré) est rattrapée, une seule fois.
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            // Jamais deux workers sur le même planning en même temps.
            ->lock($this->lockFactory->createLock('scheduler_default'))
        ;
    }
}
