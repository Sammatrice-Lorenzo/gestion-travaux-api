<?php

declare(strict_types=1);

namespace App\Interface\InvoicingPlatform;

use App\Dto\InvoicingPlatform\PlatformAttachment;
use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Exceptions\InvoicingPlatformException;
use DateTimeImmutable;

/**
 * Contrat d'accès à la plateforme agréée (PA) de facturation électronique, indépendant du fournisseur choisi.
 * Changer de PA revient à écrire une nouvelle implémentation, sans toucher au code qui en dépend.
 */
interface InvoicingPlatformClientInterface
{
    /**
     * Toutes les factures fournisseurs (hors avoirs) mises à jour dans la fenêtre, sans doublon et triées par date
     * de mise à jour croissante, même si des factures sont modifiées sur la plateforme pendant le parcours.
     *
     * @throws InvoicingPlatformException
     *
     * @return iterable<string, PlatformSupplierInvoice> factures indexées par leur id sur la plateforme
     */
    public function iterateSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo): iterable;

    /**
     * @throws InvoicingPlatformException
     */
    public function getAttachment(string $attachmentId): PlatformAttachment;

    /**
     * Télécharge le contenu binaire d'une pièce jointe à partir de son URL de téléchargement.
     *
     * @throws InvoicingPlatformException
     */
    public function downloadFile(string $url): string;
}
