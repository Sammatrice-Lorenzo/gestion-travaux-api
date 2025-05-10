<?php

namespace App\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function __invoke(): ?UserInterface
    {
        return $this->security->getUser();
    }
}
