<?php

namespace App\Controller;

use App\Repository\WorkRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;

class WorkController extends AbstractController
{
    public function __invoke(Security $security, WorkRepository $workRepository)
    {
        return $workRepository->findBy(['user' => $security->getUser()]);
    }
}
