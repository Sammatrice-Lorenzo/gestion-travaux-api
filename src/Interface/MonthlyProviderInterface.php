<?php

declare(strict_types=1);

namespace App\Interface;

use App\Entity\User;

interface MonthlyProviderInterface
{
    public function getUser(): User;
}
