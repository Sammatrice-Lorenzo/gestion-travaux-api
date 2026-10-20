<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Processor\SupplierInvoiceSyncProcessor;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demande de synchro des factures fournisseurs depuis la plateforme agréée. Elle est traitée par le worker (évite
 * un timeout HTTP quand beaucoup de factures arrivent) : réponse 202 immédiate, notification push à la fin.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/qonto/supplier_invoices/sync',
            status: Response::HTTP_ACCEPTED,
            openapi: new Operation(
                summary: 'Lance une synchronisation des factures fournisseurs Qonto',
                description: 'Réservé au compte relié à Qonto (QONTO_USER_EMAIL). Le résultat arrive par notification push.',
                security: [['bearerAuth' => []]],
            ),
            security: "is_granted('ROLE_USER')",
            input: false,
            output: SupplierInvoiceSyncStatus::class,
            processor: SupplierInvoiceSyncProcessor::class,
        ),
    ],
)]
final class SupplierInvoiceSync {}
