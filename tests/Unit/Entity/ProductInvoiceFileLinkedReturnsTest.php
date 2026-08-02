<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use DateTime;
use App\Entity\User;
use Codeception\Test\Unit;
use App\Entity\ProductInvoiceFile;
use App\Entity\SupplierReturnInvoiceFile;

final class ProductInvoiceFileLinkedReturnsTest extends Unit
{
    public function testGetLinkedSupplierReturnsReturnsSummary(): void
    {
        $user = (new User())->setEmail('user@test.com');
        $productInvoice = (new ProductInvoiceFile())
            ->setUser($user)
            ->setName('invoice.pdf')
            ->setPath('invoice.pdf')
            ->setDate(new DateTime('2026-03-10'))
            ->setTotalAmount(120.0)
        ;

        $returnInvoice = (new SupplierReturnInvoiceFile())
            ->setUser($user)
            ->setName('avoir.pdf')
            ->setPath('avoir.pdf')
            ->setDate(new DateTime('2026-03-12'))
            ->setCreditAmount(45.5)
            ->setLinkedProductInvoice($productInvoice)
        ;
        $this->setEntityId($returnInvoice, 12);

        $productInvoice->getLinkedSupplierReturnInvoices()->add($returnInvoice);

        $this->assertSame(
            [
                [
                    'id' => 12,
                    'name' => 'avoir.pdf',
                    'creditAmount' => 45.5,
                    'date' => '2026-03-12',
                ],
            ],
            $productInvoice->getLinkedSupplierReturns(),
        );
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
