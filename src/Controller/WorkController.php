<?php

namespace App\Controller;

use App\Entity\Work;
use App\Repository\WorkRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkController extends AbstractController
{
    /**
     * @return Work[]
     */
    public function __invoke(Security $security, WorkRepository $workRepository): array
    {
        return $workRepository->findBy(['user' => $security->getUser()]);
    }
}
