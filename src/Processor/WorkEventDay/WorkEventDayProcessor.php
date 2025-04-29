<?php

namespace App\Processor\WorkEventDay;

use App\Entity\User;
use App\Entity\WorkEventDay;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<WorkEventDay, WorkEventDay|void>
 */
final class WorkEventDayProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $processorInterface,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return void|WorkEventDay
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof WorkEventDay) {
            return $this->processorInterface->process($data, $operation, $uriVariables, $context);
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        if ($operation instanceof Post) {
            /** @var User $user */
            $user = $userRepository->find($this->security->getUser()->getId());
            $data->setUser($user);

            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }

        return $data;
    }
}
