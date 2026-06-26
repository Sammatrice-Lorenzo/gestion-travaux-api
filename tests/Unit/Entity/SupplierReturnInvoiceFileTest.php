<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Supplier;
use App\Entity\ProductInvoiceFile;
use App\Tests\Enum\TestPdfFileEnum;
use App\Tests\Enum\UserFixturesEnum;
use Symfony\Component\HttpFoundation\File\File;
use App\Entity\SupplierReturnInvoiceFile;

final class SupplierReturnInvoiceFileTest extends AbstractEntityTestDefault
{
    private const float CREDIT_AMOUNT = 150.50;

    private DateTimeImmutable $date;

    private Supplier $supplier;

    private User $user;

    private ProductInvoiceFile $linkedProductInvoice;

    public function _before(): void
    {
        $this->date = new DateTimeImmutable();

        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;

        /** @var Supplier $supplier */
        $supplier = $this->tester->grabEntity(Supplier::class);
        $this->supplier = $supplier;

        $this->linkedProductInvoice = (new ProductInvoiceFile())
            ->setName(TestPdfFileEnum::INVOICE_TEMPLATE->value)
            ->setDate($this->date)
            ->setPath('linked-invoice.pdf')
            ->setTotalAmount(120.0)
            ->setUser($this->user)
        ;
    }

    public function testRightEntity(): void
    {
        $supplierReturnInvoiceFile = $this->generateValidEntity();

        $this->tester->assertEquals($supplierReturnInvoiceFile->getDate(), $this->date);
        $this->tester->assertEquals($supplierReturnInvoiceFile->getCreditAmount(), self::CREDIT_AMOUNT);
        $this->tester->assertEquals($supplierReturnInvoiceFile->getSupplier(), $this->supplier);
        $this->tester->assertEquals($supplierReturnInvoiceFile->getLinkedProductInvoice(), $this->linkedProductInvoice);
        $this->assertInstanceOf(File::class, $supplierReturnInvoiceFile->getFile());
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(2, new SupplierReturnInvoiceFile());
    }

    public function generateValidEntity(): SupplierReturnInvoiceFile
    {
        return (new SupplierReturnInvoiceFile())
            ->setName('Fake supplier return invoice')
            ->setDate($this->date)
            ->setPath('fake-supplier-return.pdf')
            ->setFile(new File(TestPdfFileEnum::path()))
            ->setCreditAmount(self::CREDIT_AMOUNT)
            ->setSupplier($this->supplier)
            ->setLinkedProductInvoice($this->linkedProductInvoice)
            ->setUser($this->user)
        ;
    }
}
