<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function __invoke()
    {
        return $this->security->getUser();
    }
}
