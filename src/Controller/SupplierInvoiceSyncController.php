<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Message\SyncSupplierInvoicesMessage;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lance une synchro des factures fournisseurs à la demande. Elle est traitée par le worker (évite un timeout HTTP
 * quand beaucoup de factures arrivent) : réponse 202 immédiate, notification push à la fin.
 */
final class SupplierInvoiceSyncController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly InvoicingPlatformAccountOwner $accountOwner,
    ) {}

    #[Route(path: '/api/qonto/supplier_invoices/sync', name: 'app_qonto_supplier_invoices_sync', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->accountOwner->isOwner($user)) {
            return $this->json(['message' => 'La synchronisation Qonto n\'est pas disponible pour ce compte.'], Response::HTTP_FORBIDDEN);
        }

        $this->messageBus->dispatch(new SyncSupplierInvoicesMessage((int) $user->getId()));

        return $this->json(['message' => 'Synchronisation lancée.'], Response::HTTP_ACCEPTED);
    }
}
