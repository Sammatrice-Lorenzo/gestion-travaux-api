<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ClientController extends AbstractController
{
    /**
     * @return Client[]
     */
    public function __invoke(Security $security, ClientRepository $clientRepository): array    
    {
        return $clientRepository->findBy(['user' => $security->getUser()]);
    }
}
