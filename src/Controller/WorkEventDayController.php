<?php

namespace App\Controller;

use App\Entity\WorkEventDay;
use App\Repository\WorkEventDayRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkEventDayController extends AbstractController
{
    /**
     * @param Security $security
     * @param WorkEventDayRepository $workEventDayRepository
     * @return WorkEventDay[]
     */
    public function __invoke(Security $security, WorkEventDayRepository $workEventDayRepository): array
    {
        return $workEventDayRepository->findBy(['user' => $security->getUser()]);
    }
}
