<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClientController extends AbstractController
{
    public function __invoke(Security $security, ClientRepository $clientRepository)
    {
        return $clientRepository->findBy(['user' => $security->getUser()]);
    }
}
