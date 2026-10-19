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
use App\Exceptions\SupplierInvoiceSyncAlreadyRunningException;
use App\Exceptions\UnsupportedImageFormatException;
use App\Interface\InvoicingPlatform\InvoicingPlatformClientInterface;
use App\Service\Pdf\ImageToPdfConverter;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use finfo;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Throwable;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

use function Symfony\Component\String\u;

/**
 * Importe les factures fournisseurs de la plateforme agréée en factures produits (ProductInvoiceFile), toujours en PDF.
 * Mise à jour par identifiant plateforme (aucun doublon). Le point de reprise n'avance pas tant qu'une erreur de la
 * plateforme reste à retenter ; une erreur permanente est signalée sans bloquer les synchros suivantes.
 */
final readonly class SupplierInvoiceSyncService
{
    private const array IGNORED_STATUSES = ['rejected', 'discarded'];

    private const string PDF_MIME_TYPE = 'application/pdf';

    private const string SUPPORTED_CURRENCY = 'EUR';

    private const string DEFAULT_FIRST_SYNC_FROM = 'first day of january this year midnight';

    private const int NAME_MAX_LENGTH = 255;

    private const float AMOUNT_TOLERANCE = 0.005;

    private const string SNAPSHOT_DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private InvoicingPlatformClientInterface $client,
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $doctrine,
        private SupplierMatcher $supplierMatcher,
        private ImageToPdfConverter $imageToPdfConverter,
        private LockFactory $lockFactory,
        private LoggerInterface $logger,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param null|DateTimeImmutable $from  début de fenêtre forcé (première synchro), sinon dernier point de reprise
     * @param null|DateTimeImmutable $until fin de fenêtre, maintenant par défaut
     *
     * @throws InvoicingPlatformException                  si la liste des factures ne peut pas être parcourue (reprise au même point)
     * @throws SupplierInvoiceSyncAlreadyRunningException si une synchro est déjà en cours pour cet utilisateur
     */
    public function sync(User $user, ?DateTimeImmutable $from = null, ?DateTimeImmutable $until = null): SupplierInvoiceSyncResult
    {
        // Une synchro manuelle et la tâche planifiée créeraient sinon les mêmes factures en double.
        $lock = $this->lockFactory->createLock('supplier-invoice-sync-' . $user->getId());
        if (!$lock->acquire()) {
            throw new SupplierInvoiceSyncAlreadyRunningException();
        }

        try {
            return $this->syncWindow(
                $user,
                $from ?? $this->findSyncState($user)?->getLastSyncedAt() ?? new DateTimeImmutable(self::DEFAULT_FIRST_SYNC_FROM),
                $until ?? new DateTimeImmutable(),
            );
        } finally {
            $lock->release();
        }
    }

    private function syncWindow(User $user, DateTimeImmutable $from, DateTimeImmutable $until): SupplierInvoiceSyncResult
    {
        $result = new SupplierInvoiceSyncResult();
        $suppliers = $this->supplierMatcher->createLookup($user);

        foreach ($this->client->iterateSupplierInvoices($from, $until) as $invoice) {
            $this->importInvoice($user, $invoice, $suppliers, $result);

            if (!$this->entityManager->isOpen()) {
                // Un flush en échec ferme l'EntityManager : il est réinitialisé pour importer les factures suivantes.
                $this->doctrine->resetManager();
                $user = $this->entityManager->find(User::class, $user->getId()) ?? throw new LogicException('Utilisateur de la synchro introuvable.');
                $suppliers = $this->supplierMatcher->createLookup($user);
            }
        }

        if (!$result->hasErrors()) {
            $state = $this->findSyncState($user) ?? (new QontoSyncState())->setUser($user);
            $state->setLastSyncedAt($until);
            $this->entityManager->persist($state);
            $this->entityManager->flush();
            $result->syncedUntil = $until;
        }

        return $result;
    }

    private function importInvoice(User $user, PlatformSupplierInvoice $invoice, SupplierLookup $suppliers, SupplierInvoiceSyncResult $result): void
    {
        if ($this->isExcluded($invoice)) {
            ++$result->skipped;

            return;
        }

        if (!$this->hasSupportedCurrency($invoice)) {
            ++$result->skipped;
            $result->unsupported[] = "{$this->label($invoice)} : devise {$invoice->currency} non prise en charge";
            $this->logger->warning('Facture fournisseur ignorée : devise non prise en charge.', ['id' => $invoice->id, 'currency' => $invoice->currency]);

            return;
        }

        try {
            $existing = $this->entityManager->getRepository(ProductInvoiceFile::class)->findOneBy(['user' => $user, 'qontoId' => $invoice->id]);
            if (null !== $existing) {
                $this->updateExisting($user, $existing, $invoice, $suppliers) ? ++$result->updated : ++$result->skipped;

                return;
            }

            $this->create($user, $invoice, $suppliers);
            ++$result->created;
        } catch (UnsupportedImageFormatException $exception) {
            ++$result->skipped;
            $result->unsupported[] = "{$this->label($invoice)} : {$exception->getMessage()}";
            $this->logger->warning('Facture fournisseur ignorée : format non pris en charge.', ['id' => $invoice->id, 'mimeType' => $exception->mimeType]);
        } catch (InvoicingPlatformException $exception) {
            $result->errors[] = "{$this->label($invoice)} : {$exception->getMessage()}";
            $this->logger->error('Import de facture fournisseur en erreur, retenté à la prochaine synchro.', ['id' => $invoice->id, 'exception' => $exception]);
        } catch (Throwable $exception) {
            // Erreur permanente (donnée, fichier, base) : la retenter bloquerait toutes les synchros suivantes.
            $result->failures[] = "{$this->label($invoice)} : {$exception->getMessage()}";
            $this->logger->error('Import de facture fournisseur impossible, ignorée jusqu\'à sa prochaine modification.', ['id' => $invoice->id, 'exception' => $exception]);
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
     * Les montants sont additionnés en euros : une autre devise fausserait les totaux. Sans devise, l'euro est supposé.
     */
    private function hasSupportedCurrency(PlatformSupplierInvoice $invoice): bool
    {
        return null === $invoice->currency || self::SUPPORTED_CURRENCY === strtoupper($invoice->currency);
    }

    /**
     * Nom, montant et date suivent la plateforme tant qu'ils n'ont pas été corrigés à la main ; le fournisseur n'est
     * renseigné que s'il manque (jamais remplacé).
     *
     * @return bool true si la facture a changé
     */
    private function updateExisting(User $user, ProductInvoiceFile $productInvoiceFile, PlatformSupplierInvoice $invoice, SupplierLookup $suppliers): bool
    {
        $previous = $productInvoiceFile->getPlatformSnapshot() ?? $this->localValues($productInvoiceFile);
        $platform = $this->platformValues($invoice, $previous);
        $local = $this->localValues($productInvoiceFile);
        $changed = false;

        if ($this->followsPlatform('name', $local, $previous, $platform, $invoice)) {
            $productInvoiceFile->setName($platform['name']);
            $changed = true;
        }

        if ($this->followsPlatform('totalAmount', $local, $previous, $platform, $invoice)) {
            $productInvoiceFile->setTotalAmount($platform['totalAmount']);
            $changed = true;
        }

        if ($this->followsPlatform('date', $local, $previous, $platform, $invoice)) {
            $productInvoiceFile->setDate(new DateTime($platform['date']));
            $changed = true;
        }

        if (null === $productInvoiceFile->getSupplier()) {
            $supplier = $this->supplierMatcher->findOrCreate($user, $invoice, $suppliers);
            if (null !== $supplier) {
                $productInvoiceFile->setSupplier($supplier);
                $changed = true;
            }
        }

        $productInvoiceFile->setPlatformSnapshot($platform);
        $this->entityManager->flush();

        return $changed;
    }

    /**
     * La valeur de la plateforme n'est appliquée que si la valeur locale est encore celle importée la dernière fois.
     *
     * @param 'date'|'name'|'totalAmount'                           $field
     * @param array{name: string, totalAmount: float, date: string} $local
     * @param array{name: string, totalAmount: float, date: string} $previous
     * @param array{name: string, totalAmount: float, date: string} $platform
     */
    private function followsPlatform(string $field, array $local, array $previous, array $platform, PlatformSupplierInvoice $invoice): bool
    {
        if ($this->isSameValue($local[$field], $platform[$field])) {
            return false;
        }

        if (!$this->isSameValue($local[$field], $previous[$field])) {
            $this->logger->info('Valeur corrigée à la main conservée malgré une modification sur la plateforme.', [
                'id' => $invoice->id,
                'field' => $field,
                'local' => $local[$field],
                'platform' => $platform[$field],
            ]);

            return false;
        }

        return true;
    }

    /**
     * Un montant relu depuis le JSON peut revenir en entier (100 au lieu de 100.0) : comparaison numérique avec tolérance.
     */
    private function isSameValue(float|int|string $value, float|int|string $other): bool
    {
        if (!is_string($value) && !is_string($other)) {
            return abs($value - $other) < self::AMOUNT_TOLERANCE;
        }

        return $value === $other;
    }

    /**
     * Télécharge la pièce jointe tout de suite (le lien expire), la convertit en PDF si c'est une image, puis Vich l'enregistre.
     *
     * @throws InvoicingPlatformException
     * @throws UnsupportedImageFormatException
     */
    private function create(User $user, PlatformSupplierInvoice $invoice, SupplierLookup $suppliers): void
    {
        $attachment = $this->client->getAttachment((string) $invoice->attachmentId);
        $content = $this->client->downloadFile($attachment->url);

        $mimeType = $this->detectMimeType($content, $attachment->fileContentType);
        if (self::PDF_MIME_TYPE !== $mimeType) {
            $content = $this->imageToPdfConverter->convert($content, $mimeType);
        }

        $fileName = $this->imageToPdfConverter->pdfFileName('' !== $attachment->fileName ? $attachment->fileName : $invoice->fileName);
        $values = $this->platformValues($invoice, [
            'name' => $fileName,
            'totalAmount' => 0.0,
            'date' => ($invoice->updatedAt ?? new DateTimeImmutable())->format(self::SNAPSHOT_DATE_FORMAT),
        ]);

        $tempDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'supplier-invoice-');
        $this->filesystem->remove($tempDir);
        $this->filesystem->mkdir($tempDir);
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        try {
            $this->filesystem->dumpFile($filePath, $content);

            $productInvoiceFile = (new ProductInvoiceFile())
                ->setUser($user)
                ->setName($values['name'])
                ->setDate(new DateTime($values['date']))
                ->setTotalAmount($values['totalAmount'])
                ->setSupplier($this->supplierMatcher->findOrCreate($user, $invoice, $suppliers))
                ->setSource(ProductInvoiceSourceEnum::QONTO)
                ->setQontoId($invoice->id)
                ->setPlatformSnapshot($values)
                ->setFile(new ReplacingFile($filePath))
            ;

            $this->entityManager->persist($productInvoiceFile);
            $this->entityManager->flush();
        } finally {
            $this->filesystem->remove($tempDir);
        }
    }

    /**
     * Valeurs de la plateforme ; un champ absent (pas encore lu par l'OCR, par exemple) garde la valeur de repli.
     *
     * @param array{name: string, totalAmount: float, date: string} $fallback
     *
     * @return array{name: string, totalAmount: float, date: string}
     */
    private function platformValues(PlatformSupplierInvoice $invoice, array $fallback): array
    {
        return [
            'name' => null !== $invoice->invoiceNumber ? u($invoice->invoiceNumber)->truncate(self::NAME_MAX_LENGTH)->toString() : $fallback['name'],
            'totalAmount' => $invoice->totalAmount ?? $fallback['totalAmount'],
            'date' => $invoice->issueDate?->format(self::SNAPSHOT_DATE_FORMAT) ?? $fallback['date'],
        ];
    }

    /**
     * @return array{name: string, totalAmount: float, date: string}
     */
    private function localValues(ProductInvoiceFile $productInvoiceFile): array
    {
        return [
            'name' => $productInvoiceFile->getName(),
            'totalAmount' => $productInvoiceFile->getTotalAmount(),
            'date' => $productInvoiceFile->getDate()->format(self::SNAPSHOT_DATE_FORMAT),
        ];
    }

    private function findSyncState(User $user): ?QontoSyncState
    {
        return $this->entityManager->getRepository(QontoSyncState::class)->findOneBy(['user' => $user]);
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
