<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Entity\ProductInvoiceFile;
use App\Entity\QontoSyncState;
use App\Entity\User;
use App\Enum\ProductInvoiceSourceEnum;
use App\Exceptions\InvoicingPlatformException;
use App\Service\InvoicingPlatform\SupplierInvoiceSyncService;
use App\Service\InvoicingPlatform\SupplierMatcher;
use App\Service\Pdf\ImageToPdfConverter;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Fake\FakeInvoicingPlatformClient;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

final class SupplierInvoiceSyncServiceTest extends Unit
{
    protected UnitTester $tester;

    private EntityManagerInterface $entityManager;

    private FakeInvoicingPlatformClient $client;

    private SupplierInvoiceSyncService $syncService;

    private User $user;

    private string $uploadDirectory;

    private DateTimeImmutable $from;

    private DateTimeImmutable $until;

    protected function _before(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->tester->grabService('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
        $this->client = new FakeInvoicingPlatformClient();
        $this->syncService = new SupplierInvoiceSyncService(
            $this->client,
            $this->entityManager,
            new SupplierMatcher($this->entityManager),
            new ImageToPdfConverter(),
            new NullLogger(),
        );

        /** @var User $user */
        $user = $this->tester->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;

        /** @var string $projectDir */
        $projectDir = $this->tester->grabParameter('kernel.project_dir');
        $this->uploadDirectory = $projectDir . '/public/products-invoice/';

        $this->from = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $this->until = new DateTimeImmutable('2026-02-01T00:00:00Z');
    }

    protected function _after(): void
    {
        foreach ($this->entityManager->getRepository(ProductInvoiceFile::class)->findBy(['user' => $this->user, 'source' => ProductInvoiceSourceEnum::QONTO]) as $productInvoiceFile) {
            $path = $this->uploadDirectory . $productInvoiceFile->getPath();
            if (null !== $productInvoiceFile->getPath() && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testCreatesProductInvoiceFromPdf(): void
    {
        $this->client->addInvoice(
            FakeInvoicingPlatformClient::invoice('sync-pdf', supplierName: 'Fournisseur Sync PDF', totalAmount: 120.5),
            content: $this->pdfContent(),
        );

        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(1, $result->created);
        $this->assertSame(['sync-pdf'], $result->createdIds);

        $productInvoiceFile = $this->findByPlatformId('sync-pdf');
        $this->assertSame(ProductInvoiceSourceEnum::QONTO, $productInvoiceFile->getSource());
        $this->assertSame('F-sync-pdf', $productInvoiceFile->getName());
        $this->assertSame(120.5, $productInvoiceFile->getTotalAmount());
        $this->assertSame('2026-01-15', $productInvoiceFile->getDate()->format('Y-m-d'));
        $this->assertSame('Fournisseur Sync PDF', $productInvoiceFile->getSupplier()?->getName());
        $this->assertStoredPdf($productInvoiceFile);
    }

    public function testSyncAgainUpdatesWithoutDuplicate(): void
    {
        $this->client->addInvoice(FakeInvoicingPlatformClient::invoice('sync-update'), content: $this->pdfContent());
        $this->syncService->sync($this->user, $this->from, $this->until);

        $this->client->addInvoice(FakeInvoicingPlatformClient::invoice('sync-update', totalAmount: 150.0, issueDate: new DateTimeImmutable('2026-01-20')));
        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(0, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertCount(1, $this->entityManager->getRepository(ProductInvoiceFile::class)->findBy(['user' => $this->user, 'qontoId' => 'sync-update']));
        $productInvoiceFile = $this->findByPlatformId('sync-update');
        $this->assertSame(150.0, $productInvoiceFile->getTotalAmount());
        $this->assertSame('2026-01-20', $productInvoiceFile->getDate()->format('Y-m-d'));

        $unchanged = $this->syncService->sync($this->user, $this->from, $this->until);
        $this->assertSame(0, $unchanged->updated);
        $this->assertSame(1, $unchanged->skipped);
    }

    public function testUpdateKeepsSupplierChangedByUser(): void
    {
        $this->client->addInvoice(FakeInvoicingPlatformClient::invoice('sync-supplier', supplierName: 'Fournisseur Qonto Sync'), content: $this->pdfContent());
        $this->syncService->sync($this->user, $this->from, $this->until);

        $chosenSupplier = $this->findByPlatformId('sync-supplier')->getSupplier();
        $this->assertNotNull($chosenSupplier);
        $chosenSupplier->setName('Fournisseur choisi à la main');
        $this->entityManager->flush();

        $this->client->addInvoice(FakeInvoicingPlatformClient::invoice('sync-supplier', supplierName: 'Fournisseur Qonto Sync', totalAmount: 99.0));
        $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame($chosenSupplier, $this->findByPlatformId('sync-supplier')->getSupplier());
    }

    public function testExcludedDocumentsAreSkipped(): void
    {
        $pdf = $this->pdfContent();
        $this->client
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-rejected', status: 'rejected'), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-discarded', status: 'discarded'), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-credit-note', isCreditNote: true), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-non-financial', isNonFinancial: true), content: $pdf)
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-no-attachment', attachmentId: null))
        ;

        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(0, $result->created);
        $this->assertSame(5, $result->skipped);
        $this->assertFalse($result->hasErrors());
    }

    public function testImageIsConvertedToPdf(): void
    {
        $this->client->addInvoice(
            FakeInvoicingPlatformClient::invoice('sync-image', invoiceNumber: null),
            fileName: 'ticket.png',
            contentType: 'image/png',
            content: $this->pngContent(),
        );

        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(1, $result->created);
        $productInvoiceFile = $this->findByPlatformId('sync-image');
        $this->assertSame('ticket.pdf', $productInvoiceFile->getName());
        $this->assertStoredPdf($productInvoiceFile);
    }

    public function testUnsupportedFormatIsReportedAndSyncPointAdvances(): void
    {
        $this->client->addInvoice(
            FakeInvoicingPlatformClient::invoice('sync-heic'),
            fileName: 'photo.heic',
            contentType: 'image/heic',
            content: random_bytes(64),
        );

        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(0, $result->created);
        $this->assertSame(1, $result->skipped);
        $this->assertCount(1, $result->unsupported);
        $this->assertStringContainsString('image/heic', $result->unsupported[0]);
        $this->assertEquals($this->until, $result->syncedUntil);
    }

    public function testSyncPointNotMovedWhenAnInvoiceFails(): void
    {
        $this->client
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-ok'), content: $this->pdfContent())
            ->addInvoice(FakeInvoicingPlatformClient::invoice('sync-broken'))
        ;

        $result = $this->syncService->sync($this->user, $this->from, $this->until);

        $this->assertSame(1, $result->created);
        $this->assertCount(1, $result->errors);
        $this->assertNull($result->syncedUntil);
        $this->assertNull($this->findSyncState()?->getLastSyncedAt());
    }

    public function testListingFailurePropagatesWithoutMovingSyncPoint(): void
    {
        $this->client->failListing(new InvoicingPlatformException('Qonto indisponible', 503));

        try {
            $this->syncService->sync($this->user, $this->from, $this->until);
            $this->fail('Une InvoicingPlatformException était attendue.');
        } catch (InvoicingPlatformException) {
            $this->assertNull($this->findSyncState()?->getLastSyncedAt());
        }
    }

    public function testNextSyncStartsFromLastSyncedAt(): void
    {
        $this->syncService->sync($this->user, $this->from, $this->until);
        $this->assertEquals($this->until, $this->findSyncState()?->getLastSyncedAt());

        $later = new DateTimeImmutable('2026-02-02T00:00:00Z');
        $this->syncService->sync($this->user, until: $later);

        $this->assertEquals($this->until, $this->client->requestedWindows[1][0]);
        $this->assertEquals($later, $this->client->requestedWindows[1][1]);
    }

    private function findByPlatformId(string $platformId): ProductInvoiceFile
    {
        $productInvoiceFile = $this->entityManager->getRepository(ProductInvoiceFile::class)->findOneBy(['user' => $this->user, 'qontoId' => $platformId]);
        $this->assertNotNull($productInvoiceFile);

        return $productInvoiceFile;
    }

    private function findSyncState(): ?QontoSyncState
    {
        return $this->entityManager->getRepository(QontoSyncState::class)->findOneBy(['user' => $this->user]);
    }

    private function assertStoredPdf(ProductInvoiceFile $productInvoiceFile): void
    {
        $path = (string) $productInvoiceFile->getPath();
        $this->assertStringEndsWith('.pdf', $path);
        $this->assertFileExists($this->uploadDirectory . $path);
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($this->uploadDirectory . $path));
    }

    private function pdfContent(): string
    {
        return (string) file_get_contents(codecept_data_dir('InvoiceTemplate.pdf'));
    }

    private function pngContent(): string
    {
        $image = imagecreatetruecolor(200, 300);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 10, 120, 200));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
