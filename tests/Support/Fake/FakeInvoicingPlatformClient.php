<?php

declare(strict_types=1);

namespace App\Tests\Support\Fake;

use App\Dto\InvoicingPlatform\PlatformAttachment;
use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Exceptions\InvoicingPlatformException;
use App\Interface\InvoicingPlatform\InvoicingPlatformClientInterface;
use DateTimeImmutable;

/**
 * Plateforme agréée en mémoire : les tests y déposent factures, pièces jointes et fichiers.
 */
final class FakeInvoicingPlatformClient implements InvoicingPlatformClientInterface
{
    /**
     * @var array<string, PlatformSupplierInvoice>
     */
    private array $invoices = [];

    /**
     * @var array<string, PlatformAttachment>
     */
    private array $attachments = [];

    /**
     * @var array<string, string>
     */
    private array $files = [];

    private ?InvoicingPlatformException $listException = null;

    /**
     * @var list<array{DateTimeImmutable, DateTimeImmutable}>
     */
    public array $requestedWindows = [];

    public function addInvoice(PlatformSupplierInvoice $invoice, ?string $fileName = null, ?string $contentType = null, ?string $content = null): self
    {
        $this->invoices[$invoice->id] = $invoice;

        if (null !== $invoice->attachmentId && null !== $content) {
            $url = "https://files.test/{$invoice->attachmentId}";
            $this->attachments[$invoice->attachmentId] = new PlatformAttachment($invoice->attachmentId, $fileName ?? 'facture.pdf', $contentType ?? 'application/pdf', $url);
            $this->files[$url] = $content;
        }

        return $this;
    }

    public function failListing(InvoicingPlatformException $exception): self
    {
        $this->listException = $exception;

        return $this;
    }

    public function reset(): void
    {
        $this->invoices = [];
        $this->attachments = [];
        $this->files = [];
        $this->listException = null;
        $this->requestedWindows = [];
    }

    public function iterateSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo): iterable
    {
        $this->requestedWindows[] = [$updatedFrom, $updatedTo];

        if (null !== $this->listException) {
            throw $this->listException;
        }

        return $this->invoices;
    }

    public function getAttachment(string $attachmentId): PlatformAttachment
    {
        return $this->attachments[$attachmentId] ?? throw new InvoicingPlatformException("Pièce jointe {$attachmentId} introuvable.", 404);
    }

    public function downloadFile(string $url): string
    {
        return $this->files[$url] ?? throw new InvoicingPlatformException('Lien de téléchargement expiré.', 403);
    }

    /**
     * Facture complète par défaut, à surcharger champ par champ.
     */
    public static function invoice(string $id, mixed ...$overrides): PlatformSupplierInvoice
    {
        $defaults = [
            'id' => $id,
            'status' => 'to_pay',
            'fileName' => "{$id}.pdf",
            'attachmentId' => "att-{$id}",
            'invoiceNumber' => "F-{$id}",
            'supplierId' => null,
            'supplierName' => null,
            'vatNumber' => null,
            'totalAmount' => 100.0,
            'currency' => 'EUR',
            'issueDate' => new DateTimeImmutable('2026-01-15'),
            'updatedAt' => new DateTimeImmutable('2026-01-16T10:00:00Z'),
            'isCreditNote' => false,
            'isNonFinancial' => false,
            'supplierStreetAddress' => null,
            'supplierZipCode' => null,
            'supplierCity' => null,
            'supplierCountryCode' => null,
        ];

        /** @var array<string, mixed> $arguments */
        $arguments = array_replace($defaults, $overrides);

        return new PlatformSupplierInvoice(...$arguments);
    }
}
