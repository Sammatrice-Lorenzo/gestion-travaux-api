<?php

namespace App\Processor;

use App\Entity\User;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\Operation;
use App\Interface\UserOwnerInterface;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<UserOwnerInterface, UserOwnerInterface|void>
 */
final class UserAssignmentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $processorInterface,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return UserOwnerInterface|void
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserOwnerInterface) {
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
