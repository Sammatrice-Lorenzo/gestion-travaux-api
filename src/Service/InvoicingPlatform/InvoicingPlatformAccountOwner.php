<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Entity\User;
use App\Exceptions\InvoicingPlatformAccountException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Seul utilisateur autorisé à synchroniser : la clé API de la plateforme donne accès aux factures de SON entreprise,
 * qu'aucun autre compte ne doit importer.
 */
final readonly class InvoicingPlatformAccountOwner
{
    private string $email;

    public function __construct(
        private EntityManagerInterface $entityManager,
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

        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $this->email])
            ?? throw new InvoicingPlatformAccountException("Aucun utilisateur pour QONTO_USER_EMAIL « {$this->email} ».");
    }

    public function isOwner(User $user): bool
    {
        return '' !== $this->email && 0 === strcasecmp((string) $user->getEmail(), $this->email);
    }
}
