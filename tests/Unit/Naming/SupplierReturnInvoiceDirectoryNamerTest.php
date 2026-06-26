<?php

declare(strict_types=1);

namespace App\Tests\Unit\Naming;

use DateTimeImmutable;
use App\Entity\Supplier;
use Codeception\Test\Unit;
use App\Tests\Enum\TestPdfFileEnum;
use App\Entity\SupplierReturnInvoiceFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use App\Naming\SupplierReturnInvoiceDirectoryNamer;

final class SupplierReturnInvoiceDirectoryNamerTest extends Unit
{
    private SupplierReturnInvoiceDirectoryNamer $directoryNamer;

    protected function _before(): void
    {
        $this->directoryNamer = new SupplierReturnInvoiceDirectoryNamer(new AsciiSlugger());
    }

    public function testBuildsDirectoryFromDateAndSupplier(): void
    {
        $entity = $this->createEntity((new Supplier())->setName('Leroy Merlin'));

        $directory = $this->directoryNamer->directoryName($entity, $this->createPropertyMapping());

        $this->assertSame('2026/06/leroy-merlin', $directory);
    }

    public function testUsesFallbackDirectoryWhenSupplierIsMissing(): void
    {
        $entity = $this->createEntity(null);

        $directory = $this->directoryNamer->directoryName($entity, $this->createPropertyMapping());

        $this->assertSame('2026/03/sans-fournisseur', $directory);
    }

    public function testThrowsForInvalidObjectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->directoryNamer->directoryName(new \stdClass(), $this->createPropertyMapping());
    }

    private function createEntity(?Supplier $supplier): SupplierReturnInvoiceFile
    {
        $entity = (new SupplierReturnInvoiceFile())
            ->setName(TestPdfFileEnum::INVOICE_TEMPLATE->value)
            ->setPath('invoice-template.pdf')
            ->setCreditAmount(10.0)
        ;

        if (null !== $supplier) {
            $entity
                ->setDate(new DateTimeImmutable('2026-06-15'))
                ->setSupplier($supplier)
            ;
        } else {
            $entity->setDate(new DateTimeImmutable('2026-03-01'));
        }

        return $entity;
    }

    private function createPropertyMapping(): PropertyMapping
    {
        return new PropertyMapping('file', 'path');
    }
}
