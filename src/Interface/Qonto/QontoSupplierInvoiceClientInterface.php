<?php

declare(strict_types=1);

namespace App\Interface\Qonto;

use App\Dto\Qonto\QontoAttachment;
use App\Dto\Qonto\QontoSupplierInvoice;
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
     * Toutes les factures fournisseurs de la fenêtre, sans doublon, avec une pagination stable même si des
     * factures sont modifiées dans Qonto pendant le parcours. À privilégier pour la synchro plutôt que listSupplierInvoices.
     *
     * @throws QontoApiException
     *
     * @return iterable<string, QontoSupplierInvoice> factures indexées par leur id Qonto
     */
    public function iterateSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo): iterable;

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
