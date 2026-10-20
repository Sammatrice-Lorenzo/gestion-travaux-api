<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Entity\User;
use App\Exceptions\InvoicingPlatformAccountException;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Seul utilisateur autorisé à synchroniser : la clé API de la plateforme donne accès aux factures de SON entreprise,
 * qu'aucun autre compte ne doit importer.
 */
final readonly class InvoicingPlatformAccountOwner
{
    private string $email;

    public function __construct(
        private UserRepository $userRepository,
        #[Autowire('%env(QONTO_USER_EMAIL)%')]
        string $email,
    ) {
        $this->email = trim($email);
    }

    /**
     * @throws InvoicingPlatformAccountException
     */
    public function getOwner(): User
    {
        if ('' === $this->email) {
            throw new InvoicingPlatformAccountException('QONTO_USER_EMAIL n\'est pas défini.');
        }

        return $this->userRepository->findOneBy(['email' => $this->email])
            ?? throw new InvoicingPlatformAccountException("Aucun utilisateur pour QONTO_USER_EMAIL « {$this->email} ».");
    }

    /**
     * Même règle que getOwner() (recherche en base) : la tâche planifiée et la synchro à la demande ne peuvent pas
     * désigner deux propriétaires différents selon la casse de l'e-mail.
     */
    public function isOwner(User $user): bool
    {
        try {
            return null !== $user->getId() && $this->getOwner()->getId() === $user->getId();
        } catch (InvoicingPlatformAccountException) {
            return false;
        }
    }
}
