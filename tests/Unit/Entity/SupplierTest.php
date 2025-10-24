<?php

namespace App\Tests\Unit\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\Supplier;
use App\Entity\ProductInvoiceFile;
use App\Tests\Enum\UserFixturesEnum;
use Symfony\Component\HttpFoundation\File\File;

final class SupplierTest extends AbstractEntityTestDefault
{
    private const string CITY = 'Paris';

    private User $user;

    private ProductInvoiceFile $productInvoiceFile;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
        $this->productInvoiceFile = $this->generateProductInvoiceFile();
    }

    public function testRightEntity(): void
    {
        $supplier = $this->generateValidEntity();

        $this->tester->assertEquals($supplier->getUser(), $this->user);
        $this->tester->assertEquals($supplier->getCity(), self::CITY);

        $this->tester->assertTrue(
            in_array($this->productInvoiceFile, $supplier->getProductInvoiceFiles()->toArray())
        );
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(4, new Supplier());
    }

    private function generateValidEntity(): Supplier
    {
        return (new Supplier())
            ->setName('Supplier Test')
            ->setCity(self::CITY)
            ->setAddress('80 rue du Test')
            ->setPhone('0123456789')
            ->setCountry('France')
            ->setUser($this->user)
            ->addProductInvoiceFile($this->productInvoiceFile)
        ;
    }

    public function generateProductInvoiceFile(): ProductInvoiceFile
    {
        $filePath = codecept_data_dir('InvoiceTemplate.pdf');

        return (new ProductInvoiceFile())
            ->setName('Fake Product invocie for supplier')
            ->setDate(new DateTime())
            ->setFile(new File($filePath))
            ->setTotalAmount(150)
            ->setUser($this->user)
        ;
    }
}
