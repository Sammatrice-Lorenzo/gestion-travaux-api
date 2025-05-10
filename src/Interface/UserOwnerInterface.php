<?php

namespace App\Interface;

use App\Entity\User;

interface UserOwnerInterface
{
    public function getUser(): User;

    public function setUser(User $user): mixed;
}
