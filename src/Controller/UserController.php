<?php

namespace App\Controller;

use Amp\Http\Client\Request;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
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

    public function getUserById(Request $request, UserRepository $userRepo): ?User
    {
        if ($this->security->getUser()->getId() == $request['id']) {
            return $this->security->getUser();
        }

        return $userRepo->findOneBy(['id' => $request['id']]);
    }
}
