<?php

namespace App\Controller;

use App\Entity\User;
use Amp\Http\Client\Request;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepo
    ) {}

    public function __invoke(): ?UserInterface
    {
        return $this->security->getUser();
    }

    public function getUserById(Request $request): ?User
    {
        if ($this->security->getUser()->getId() == $request['id']) {
            return $this->security->getUser();
        }

        return $this->userRepo->findOneBy(['id' => $request['id']]);
    }
}
