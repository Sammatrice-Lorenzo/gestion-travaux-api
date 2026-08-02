<?php

declare(strict_types=1);

namespace App\Interface;

use App\Entity\User;

interface UserOwnerInterface
{
    public function getUser(): User;

    public function setUser(User $user): mixed;
}
