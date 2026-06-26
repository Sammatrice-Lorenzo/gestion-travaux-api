<?php

declare(strict_types=1);

namespace App\Tests\Unit\Naming;

use DateTimeImmutable;
use App\Entity\Supplier;
use Codeception\Test\Unit;
use App\Tests\Enum\TestPdfFileEnum;
use App\Entity\SupplierReturnInvoiceFile;
use App\Naming\SupplierReturnInvoiceNamer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class SupplierReturnInvoiceNamerTest extends Unit
{
    private SupplierReturnInvoiceNamer $namer;

    protected function _before(): void
    {
        $this->namer = new SupplierReturnInvoiceNamer(new AsciiSlugger());
    }

    public function testGeneratesSluggedFileNameWithExtension(): void
    {
        $entity = $this->createEntity();

        $fileName = $this->namer->name($entity, $this->createPropertyMapping());

        $this->assertMatchesRegularExpression('/^invoicetemplate_[a-f0-9]{12}\.pdf$/', $fileName);
    }

    public function testThrowsForInvalidObjectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->namer->name(new \stdClass(), $this->createPropertyMapping());
    }

    private function createEntity(): SupplierReturnInvoiceFile
    {
        return (new SupplierReturnInvoiceFile())
            ->setName(TestPdfFileEnum::INVOICE_TEMPLATE->value)
            ->setDate(new DateTimeImmutable('2026-06-15'))
            ->setPath('temporary.pdf')
            ->setCreditAmount(10.0)
            ->setSupplier((new Supplier())->setName('Amazon'))
            ->setFile(new UploadedFile(
                TestPdfFileEnum::path(),
                TestPdfFileEnum::INVOICE_TEMPLATE->value,
                'application/pdf',
                null,
                true,
            ))
        ;
    }

    private function createPropertyMapping(): PropertyMapping
    {
        return new PropertyMapping('file', 'path');
    }
}
