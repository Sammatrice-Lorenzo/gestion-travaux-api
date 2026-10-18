<?php

declare(strict_types=1);

namespace App\Interface\Qonto;

use App\Dto\Qonto\QontoAttachment;
use App\Dto\Qonto\QontoSupplierInvoicePage;
use App\Exceptions\QontoApiException;
use DateTimeImmutable;

interface QontoSupplierInvoiceClientInterface
{
    /**
     * Factures fournisseurs (hors avoirs) mises à jour dans la fenêtre donnée, triées par updated_at croissant.
     *
     * @throws QontoApiException
     */
    public function listSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo, int $page = 1): QontoSupplierInvoicePage;

    /**
     * @throws QontoApiException
     */
    public function getAttachment(string $attachmentId): QontoAttachment;

    /**
     * Télécharge le contenu binaire d'une URL de pièce jointe (lien signé, sans authentification Qonto).
     *
     * @throws QontoApiException
     */
    public function downloadFile(string $url): string;
}
