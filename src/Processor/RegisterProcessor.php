<?php

namespace App\Processor;

use App\Entity\User;
use App\Dto\RegisterInput;
use App\Security\EmailVerifier;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EmailVerifier $emailVerifier,
        private readonly ParameterBagInterface $parameterBagInterface
    ) {}

    /**
     * @param RegisterInput $data
     *
     * @return User
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = (new User())
            ->setFirstname($data->firstname)
            ->setLastname($data->lastname)
            ->setEmail($data->email)
            ->setRoles(['ROLE_USER'])
        ;

        $user->setPassword($this->hasher->hashPassword($user, $data->password));

        $this->em->persist($user);
        $this->em->flush();

        if ('test' !== $this->parameterBagInterface->get('env')) {
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address($this->parameterBagInterface->get('mail_username')))
                    ->to(new Address($user->getEmail()))
                    ->subject('Confirmer votre compte')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
        }

        return $user;
    }
}
