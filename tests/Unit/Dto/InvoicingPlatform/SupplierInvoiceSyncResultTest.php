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

        $this->assertSame([
            'Bricomat' => ['created' => 2, 'updated' => 1, 'skipped' => 0, 'failed' => 0],
            'Peintures Martin' => ['created' => 0, 'updated' => 0, 'skipped' => 1, 'failed' => 1],
            'Bois & Co' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 1],
        ], $result->suppliers);

        $this->assertSame(2, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertSame(1, $result->skipped);
        $this->assertSame(['Peintures Martin / F-1 : Qonto indisponible'], $result->errors);
        $this->assertSame(['Bois & Co / F-2 : disque plein'], $result->failures);
    }

    public function testInvoiceWithoutSupplierNameIsCountedAsUnknown(): void
    {
        $result = new SupplierInvoiceSyncResult();
        $result->addCreated(null);
        $result->addSkipped('   ');

        $this->assertSame(
            [SupplierInvoiceSyncResult::UNKNOWN_SUPPLIER => ['created' => 1, 'updated' => 0, 'skipped' => 1, 'failed' => 0]],
            $result->suppliers,
        );
    }

    public function testReportContainsSupplierBreakdown(): void
    {
        $result = new SupplierInvoiceSyncResult();
        $result->addCreated('Bricomat');

        $report = $result->toReport();

        $this->assertSame(1, $report['created']);
        $this->assertSame($result->suppliers, $report['suppliers']);
    }
}
