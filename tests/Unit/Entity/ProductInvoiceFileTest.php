<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Supplier;
use App\Entity\ProductInvoiceFile;
use App\Tests\Enum\UserFixturesEnum;
use Symfony\Component\HttpFoundation\File\File;

final class ProductInvoiceFileTest extends AbstractEntityTestDefault
{
    private const float TOTAL_AMOUNT = 150.50;

    private DateTimeImmutable $date;

    private Supplier $supplier;

    private User $user;

    public function _before(): void
    {
        $this->date = new DateTimeImmutable();

        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;

        /** @var Supplier $supplier */
        $supplier = $this->tester->grabEntity(Supplier::class);
        $this->supplier = $supplier;
    }

    public function testRightEntity(): void
    {
        $productInvoiceFile = $this->generateValidEntity();

        $this->tester->assertEquals($productInvoiceFile->getDate(), $this->date);
        $this->tester->assertEquals($productInvoiceFile->getTotalAmount(), self::TOTAL_AMOUNT);
        $this->tester->assertEquals($productInvoiceFile->getSupplier(), $this->supplier);
        $this->assertInstanceOf(File::class, $productInvoiceFile->getFile());
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(2, new ProductInvoiceFile());
    }

    public function generateValidEntity(): ProductInvoiceFile
    {
        $filePath = codecept_data_dir('InvoiceTemplate.pdf');

        return (new ProductInvoiceFile())
            ->setName('Fake Product invocie')
            ->setDate($this->date)
            ->setFile(new File($filePath))
            ->setTotalAmount(self::TOTAL_AMOUNT)
            ->setSupplier($this->supplier)
            ->setUser($this->user)
        ;
    }
}
