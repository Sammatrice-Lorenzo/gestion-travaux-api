<?php

declare(strict_types=1);

namespace App\Tests\Unit\Processor;

use DateTime;
use App\Entity\User;
use App\Entity\Supplier;
use Codeception\Test\Unit;
use ApiPlatform\Metadata\Put;
use App\Entity\ProductInvoiceFile;
use App\Entity\SupplierReturnInvoiceFile;
use App\Dto\SupplierReturnInvoiceUpdateInput;
use App\Processor\SupplierReturnInvoiceProcessor;
use App\Service\SupplierReturnInvoiceLinkValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;

final class SupplierReturnInvoiceProcessorTest extends Unit
{
    private EntityManagerInterface&MockObject $entityManager;

    private SupplierReturnInvoiceLinkValidator $linkValidator;

    private SupplierReturnInvoiceProcessor $processor;

    protected function _before(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->linkValidator = new SupplierReturnInvoiceLinkValidator($this->entityManager);
        $this->processor = new SupplierReturnInvoiceProcessor(
            $this->entityManager,
            $this->linkValidator,
        );
    }

    public function testUpdatesSupplierReturnInvoiceFields(): void
    {
        $user = (new User())->setEmail('user@test.com');
        $supplier = (new Supplier())
            ->setName('Amazon')
            ->setAddress('1 rue Test')
            ->setCity('Paris')
            ->setCountry('France')
            ->setVatNumber('FR12345678901')
            ->setUser($user)
        ;
        $this->setEntityId($supplier, 12);

        $productInvoice = (new ProductInvoiceFile())
            ->setUser($user)
            ->setName('linked.pdf')
            ->setPath('linked.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setTotalAmount(50.0)
            ->setSupplier($supplier)
        ;
        $this->setEntityId($productInvoice, 34);

        $invoice = (new SupplierReturnInvoiceFile())
            ->setUser($user)
            ->setName('initial-name.pdf')
            ->setPath('initial-name.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setCreditAmount(12.5)
        ;
        $this->setEntityId($invoice, 7);

        $invoiceRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $invoice,
        ]);
        $supplierRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $supplier,
        ]);
        $productInvoiceRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $productInvoice,
        ]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnMap([
                [SupplierReturnInvoiceFile::class, $invoiceRepository],
                [Supplier::class, $supplierRepository],
                [ProductInvoiceFile::class, $productInvoiceRepository],
            ])
        ;
        $this->entityManager->expects($this->once())->method('flush');

        $input = new SupplierReturnInvoiceUpdateInput();
        $input->name = 'updated-name.pdf';
        $input->date = '2026-02-20';
        $input->creditAmount = 99.9;
        $input->supplierId = 12;
        $input->linkedProductInvoiceId = 34;

        $updated = $this->processor->process($input, new Put(), ['id' => 7]);

        $this->assertSame('updated-name.pdf', $updated->getName());
        $this->assertSame('2026-02-20', $updated->getDate()->format('Y-m-d'));
        $this->assertSame(99.9, $updated->getCreditAmount());
        $this->assertSame(12, $updated->getSupplier()?->getId());
        $this->assertSame(34, $updated->getLinkedProductInvoice()?->getId());
    }

    public function testClearsOptionalRelationsWhenIdsAreNull(): void
    {
        $user = (new User())->setEmail('user@test.com');
        $supplier = (new Supplier())
            ->setName('Amazon')
            ->setAddress('1 rue Test')
            ->setCity('Paris')
            ->setCountry('France')
            ->setVatNumber('FR12345678901')
            ->setUser($user)
        ;
        $this->setEntityId($supplier, 12);

        $productInvoice = (new ProductInvoiceFile())
            ->setUser($user)
            ->setName('linked.pdf')
            ->setPath('linked.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setTotalAmount(50.0)
            ->setSupplier($supplier)
        ;
        $this->setEntityId($productInvoice, 34);

        $invoice = (new SupplierReturnInvoiceFile())
            ->setUser($user)
            ->setName('initial-name.pdf')
            ->setPath('initial-name.pdf')
            ->setDate(new DateTime('2026-01-10'))
            ->setCreditAmount(12.5)
            ->setSupplier($supplier)
            ->setLinkedProductInvoice($productInvoice)
        ;
        $this->setEntityId($invoice, 7);

        $invoiceRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => $invoice,
        ]);
        $supplierRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => null,
        ]);
        $productInvoiceRepository = $this->createConfiguredMock(EntityRepository::class, [
            'find' => null,
        ]);

        $this->entityManager
            ->method('getRepository')
            ->willReturnMap([
                [SupplierReturnInvoiceFile::class, $invoiceRepository],
                [Supplier::class, $supplierRepository],
                [ProductInvoiceFile::class, $productInvoiceRepository],
            ])
        ;
        $this->entityManager->expects($this->once())->method('flush');

        $input = new SupplierReturnInvoiceUpdateInput();
        $input->name = 'cleared-relations.pdf';
        $input->date = '2026-03-01';
        $input->creditAmount = 10.0;
        $input->supplierId = null;
        $input->linkedProductInvoiceId = null;

        $updated = $this->processor->process($input, new Put(), ['id' => 7]);

        $this->assertNull($updated->getSupplier());
        $this->assertNull($updated->getLinkedProductInvoice());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
