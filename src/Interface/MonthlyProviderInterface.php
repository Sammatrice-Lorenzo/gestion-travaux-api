<?php

namespace App\Interface;

use App\Entity\User;

interface MonthlyProviderInterface
{
    public function getUser(): User;
}
