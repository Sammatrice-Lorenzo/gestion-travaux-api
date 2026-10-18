<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\ProductInvoiceFile;
use App\Entity\QontoSyncState;
use App\Entity\User;
use App\Enum\ProductInvoiceSourceEnum;
use App\Exceptions\InvoicingPlatformException;
use App\Exceptions\UnsupportedImageFormatException;
use App\Interface\InvoicingPlatform\InvoicingPlatformClientInterface;
use App\Service\Pdf\ImageToPdfConverter;
use DateTime;
use DateTimeImmutable;
use finfo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

/**
 * Importe les factures fournisseurs de la plateforme agréée en factures produits (ProductInvoiceFile), toujours en PDF.
 * Mise à jour par identifiant plateforme (aucun doublon) ; le point de reprise n'avance qu'après une synchro sans erreur.
 */
final readonly class SupplierInvoiceSyncService
{
    private const array IGNORED_STATUSES = ['rejected', 'discarded'];

    private const string PDF_MIME_TYPE = 'application/pdf';

    private const string DEFAULT_FIRST_SYNC_FROM = 'first day of january this year midnight';

    public function __construct(
        private InvoicingPlatformClientInterface $client,
        private EntityManagerInterface $entityManager,
        private SupplierMatcher $supplierMatcher,
        private ImageToPdfConverter $imageToPdfConverter,
        private LoggerInterface $logger,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param null|DateTimeImmutable $from  début de fenêtre forcé (première synchro), sinon dernier point de reprise
     * @param null|DateTimeImmutable $until fin de fenêtre, maintenant par défaut
     *
     * @throws InvoicingPlatformException si la liste des factures ne peut pas être parcourue (rien n'est perdu : reprise au même point)
     */
    public function sync(User $user, ?DateTimeImmutable $from = null, ?DateTimeImmutable $until = null): SupplierInvoiceSyncResult
    {
        $state = $this->entityManager->getRepository(QontoSyncState::class)->findOneBy(['user' => $user])
            ?? (new QontoSyncState())->setUser($user);

        $from ??= $state->getLastSyncedAt() ?? new DateTimeImmutable(self::DEFAULT_FIRST_SYNC_FROM);
        $until ??= new DateTimeImmutable();

        $result = new SupplierInvoiceSyncResult();
        foreach ($this->client->iterateSupplierInvoices($from, $until) as $invoice) {
            $this->importInvoice($user, $invoice, $result);
        }

        if (!$result->hasErrors()) {
            $state->setLastSyncedAt($until);
            $this->entityManager->persist($state);
            $this->entityManager->flush();
            $result->syncedUntil = $until;
        }

        return $result;
    }

    private function importInvoice(User $user, PlatformSupplierInvoice $invoice, SupplierInvoiceSyncResult $result): void
    {
        if ($this->isExcluded($invoice)) {
            ++$result->skipped;

            return;
        }

        $existing = $this->entityManager->getRepository(ProductInvoiceFile::class)->findOneBy(['user' => $user, 'qontoId' => $invoice->id]);

        try {
            if (null !== $existing) {
                $this->updateExisting($user, $existing, $invoice) ? ++$result->updated : ++$result->skipped;

                return;
            }

            $this->create($user, $invoice);
            ++$result->created;
            $result->createdIds[] = $invoice->id;
        } catch (UnsupportedImageFormatException $exception) {
            ++$result->skipped;
            $result->unsupported[] = "{$this->label($invoice)} : {$exception->getMessage()}";
            $this->logger->warning('Facture fournisseur ignorée : format non pris en charge.', ['id' => $invoice->id, 'mimeType' => $exception->mimeType]);
        } catch (InvoicingPlatformException $exception) {
            $result->errors[] = "{$this->label($invoice)} : {$exception->getMessage()}";
            $this->logger->error('Import de facture fournisseur en erreur.', ['id' => $invoice->id, 'exception' => $exception]);
        }
    }

    /**
     * Les rejets, les avoirs (exclus en V1), les documents non financiers et les factures sans fichier ne sont pas importés.
     */
    private function isExcluded(PlatformSupplierInvoice $invoice): bool
    {
        return in_array($invoice->status, self::IGNORED_STATUSES, true)
            || $invoice->isCreditNote
            || $invoice->isNonFinancial
            || null === $invoice->attachmentId;
    }

    /**
     * Montant et date suivent la plateforme ; le fournisseur n'est renseigné que s'il manque (jamais remplacé).
     *
     * @return bool true si la facture a changé
     */
    private function updateExisting(User $user, ProductInvoiceFile $productInvoiceFile, PlatformSupplierInvoice $invoice): bool
    {
        $changed = false;

        if (null !== $invoice->totalAmount && abs($productInvoiceFile->getTotalAmount() - $invoice->totalAmount) >= 0.005) {
            $productInvoiceFile->setTotalAmount($invoice->totalAmount);
            $changed = true;
        }

        if (null !== $invoice->issueDate && $productInvoiceFile->getDate()->format('Y-m-d') !== $invoice->issueDate->format('Y-m-d')) {
            $productInvoiceFile->setDate(DateTime::createFromImmutable($invoice->issueDate));
            $changed = true;
        }

        if (null === $productInvoiceFile->getSupplier()) {
            $supplier = $this->supplierMatcher->findOrCreate($user, $invoice);
            if (null !== $supplier) {
                $productInvoiceFile->setSupplier($supplier);
                $changed = true;
            }
        }

        if ($changed) {
            $this->entityManager->flush();
        }

        return $changed;
    }

    /**
     * Télécharge la pièce jointe tout de suite (le lien expire), la convertit en PDF si c'est une image, puis Vich l'enregistre.
     *
     * @throws InvoicingPlatformException
     * @throws UnsupportedImageFormatException
     */
    private function create(User $user, PlatformSupplierInvoice $invoice): void
    {
        $attachment = $this->client->getAttachment((string) $invoice->attachmentId);
        $content = $this->client->downloadFile($attachment->url);

        $mimeType = $this->detectMimeType($content, $attachment->fileContentType);
        if (self::PDF_MIME_TYPE !== $mimeType) {
            $content = $this->imageToPdfConverter->convert($content, $mimeType);
        }

        $fileName = ImageToPdfConverter::pdfFileName('' !== $attachment->fileName ? $attachment->fileName : $invoice->fileName);
        $tempDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'supplier-invoice-');
        $this->filesystem->remove($tempDir);
        $this->filesystem->mkdir($tempDir);
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        try {
            $this->filesystem->dumpFile($filePath, $content);

            $productInvoiceFile = (new ProductInvoiceFile())
                ->setUser($user)
                ->setName($invoice->invoiceNumber ?? $fileName)
                ->setDate(DateTime::createFromImmutable($invoice->issueDate ?? $invoice->updatedAt ?? new DateTimeImmutable()))
                ->setTotalAmount($invoice->totalAmount ?? 0.0)
                ->setSupplier($this->supplierMatcher->findOrCreate($user, $invoice))
                ->setSource(ProductInvoiceSourceEnum::QONTO)
                ->setQontoId($invoice->id)
                ->setFile(new ReplacingFile($filePath))
            ;

            $this->entityManager->persist($productInvoiceFile);
            $this->entityManager->flush();
        } finally {
            $this->filesystem->remove($tempDir);
        }
    }

    /**
     * Le type annoncé par la plateforme peut être faux ou vide : le contenu fait foi.
     */
    private function detectMimeType(string $content, string $declaredMimeType): string
    {
        $detected = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

        return strtolower(is_string($detected) && 'application/octet-stream' !== $detected ? $detected : $declaredMimeType);
    }

    private function label(PlatformSupplierInvoice $invoice): string
    {
        return $invoice->invoiceNumber ?? ('' !== $invoice->fileName ? $invoice->fileName : $invoice->id);
    }
}
