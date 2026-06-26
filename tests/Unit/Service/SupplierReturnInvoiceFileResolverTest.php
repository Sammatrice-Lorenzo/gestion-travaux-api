<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use Codeception\Test\Unit;
use App\Tests\Support\UnitTester;
use App\Entity\SupplierReturnInvoiceFile;
use App\Tests\Enum\TestPdfFileEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Storage\StorageInterface;
use App\Service\SupplierReturnInvoiceFileResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class SupplierReturnInvoiceFileResolverTest extends Unit
{
    protected UnitTester $tester;

    private string $baseDir;

    protected function _before(): void
    {
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'supplier-return-tests-' . uniqid();
        mkdir($this->baseDir, 0777, true);
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->baseDir);
    }

    public function testResolveAbsolutePathUsesVichStorageWhenAvailable(): void
    {
        $invoice = $this->createInvoice('stored.pdf');
        $expectedPath = $this->baseDir . DIRECTORY_SEPARATOR . 'stored.pdf';
        touch($expectedPath);

        $resolver = $this->createResolver($expectedPath, null);

        $this->assertSame($expectedPath, $resolver->resolveAbsolutePath($invoice));
    }

    public function testResolveAbsolutePathFallsBackToNestedStoredPath(): void
    {
        $invoice = $this->createInvoice('2026/06/amazon/invoice-template.pdf');
        $expectedPath = $this->baseDir . DIRECTORY_SEPARATOR . '2026' . DIRECTORY_SEPARATOR . '06' . DIRECTORY_SEPARATOR . 'amazon' . DIRECTORY_SEPARATOR . 'invoice-template.pdf';
        mkdir(dirname($expectedPath), 0777, true);
        touch($expectedPath);

        $resolver = $this->createResolver(null, null);

        $this->assertSame($expectedPath, $resolver->resolveAbsolutePath($invoice));
    }

    public function testResolveAbsolutePathFallsBackToFlatFileName(): void
    {
        $invoice = $this->createInvoice('2026/06/amazon/invoice-template.pdf');
        $expectedPath = $this->baseDir . DIRECTORY_SEPARATOR . 'invoice-template.pdf';
        touch($expectedPath);

        $resolver = $this->createResolver(null, null);

        $this->assertSame($expectedPath, $resolver->resolveAbsolutePath($invoice));
    }

    public function testResolveAbsolutePathThrowsWhenFileIsMissing(): void
    {
        $invoice = $this->createInvoice('missing.pdf');

        $resolver = $this->createResolver(null, null);

        $this->expectException(NotFoundHttpException::class);
        $resolver->resolveAbsolutePath($invoice);
    }

    public function testResolveRelativePathStripsBaseDirectory(): void
    {
        $invoice = $this->createInvoice('nested/invoice-template.pdf');
        $absolutePath = $this->baseDir . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'invoice-template.pdf';
        mkdir(dirname($absolutePath), 0777, true);
        touch($absolutePath);

        $resolver = $this->createResolver(null, null);

        $this->assertSame('nested' . DIRECTORY_SEPARATOR . 'invoice-template.pdf', $resolver->resolveRelativePath($invoice));
    }

    private function createResolver(?string $vichPath, ?string $unused): SupplierReturnInvoiceFileResolver
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolvePath')->willReturn($vichPath);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->with('supplier_return_invoices')->willReturn($this->baseDir);

        return new SupplierReturnInvoiceFileResolver($parameterBag, $storage);
    }

    private function createInvoice(string $path): SupplierReturnInvoiceFile
    {
        return (new SupplierReturnInvoiceFile())
            ->setName(TestPdfFileEnum::INVOICE_TEMPLATE->value)
            ->setDate(new \DateTimeImmutable('2026-06-15'))
            ->setPath($path)
            ->setCreditAmount(10.0)
            ->setFile(new UploadedFile(
                TestPdfFileEnum::path(),
                TestPdfFileEnum::INVOICE_TEMPLATE->value,
                'application/pdf',
                null,
                true,
            ))
        ;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
