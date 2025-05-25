<?php

namespace App\Interface;

use App\Entity\User;
use DateTime;

interface MonthlyProviderRepositoryInterface
{
    /**
     * @return MonthlyProviderInterface[]
     */
    public function findByMonth(User $user, DateTime $date): array;
}
