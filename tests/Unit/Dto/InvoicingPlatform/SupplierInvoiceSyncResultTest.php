<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\InvoicingPlatform;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use Codeception\Test\Unit;

final class SupplierInvoiceSyncResultTest extends Unit
{
    public function testCountsOutcomesPerSupplierAndInTotal(): void
    {
        $result = new SupplierInvoiceSyncResult();
        $result->addCreated('Bricomat');
        $result->addCreated('Bricomat');
        $result->addUpdated('Bricomat');
        $result->addSkipped('Peintures Martin');
        $result->addError('Peintures Martin', 'Peintures Martin / F-1 : Qonto indisponible');
        $result->addFailure('Bois & Co', 'Bois & Co / F-2 : disque plein');
        $result->addUnsupported('Bois & Co', 'Bois & Co / F-3 : devise USD');

        $this->assertSame([
            'Bricomat' => ['created' => 2, 'updated' => 1, 'skipped' => 0, 'failed' => 0],
            'Peintures Martin' => ['created' => 0, 'updated' => 0, 'skipped' => 1, 'failed' => 1],
            'Bois & Co' => ['created' => 0, 'updated' => 0, 'skipped' => 1, 'failed' => 1],
        ], $result->suppliers());

        $this->assertSame(2, $result->created());
        $this->assertSame(1, $result->updated());
        $this->assertSame(2, $result->skipped());
        $this->assertSame(['Peintures Martin / F-1 : Qonto indisponible'], $result->errors());
        $this->assertSame(['Bois & Co / F-2 : disque plein'], $result->failures());
        $this->assertSame(['Bois & Co / F-3 : devise USD'], $result->unsupported());
    }

    public function testEmptyResultHasZeroTotals(): void
    {
        $result = new SupplierInvoiceSyncResult();

        $this->assertSame(0, $result->created());
        $this->assertSame(0, $result->updated());
        $this->assertSame(0, $result->skipped());
        $this->assertTrue($result->isSuccessful());
    }

    public function testInvoiceWithoutSupplierNameIsCountedAsUnknown(): void
    {
        $result = new SupplierInvoiceSyncResult();
        $result->addCreated(null);
        $result->addSkipped('   ');

        $this->assertSame(
            [SupplierInvoiceSyncResult::UNKNOWN_SUPPLIER => ['created' => 1, 'updated' => 0, 'skipped' => 1, 'failed' => 0]],
            $result->suppliers(),
        );
    }
}
