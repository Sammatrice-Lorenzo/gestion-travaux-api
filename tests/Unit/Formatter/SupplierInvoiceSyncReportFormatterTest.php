<?php

declare(strict_types=1);

namespace App\Tests\Unit\Formatter;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Formatter\SupplierInvoiceSyncReportFormatter;
use Codeception\Test\Unit;

final class SupplierInvoiceSyncReportFormatterTest extends Unit
{
    public function testEmptySyncIsASingleLine(): void
    {
        $this->assertSame(
            'Créées : 0, mises à jour : 0, ignorées : 0',
            SupplierInvoiceSyncReportFormatter::format(new SupplierInvoiceSyncResult()),
        );
    }

    public function testReportListsSuppliersAndIssues(): void
    {
        $result = new SupplierInvoiceSyncResult();
        $result->addCreated('Société Générale');
        $result->addUnsupported('Bricomat', 'Bricomat / F-3 : devise USD non prise en charge');
        $result->addError('Bois & Co', 'Bois & Co / F-1 : Qonto indisponible');
        $result->addFailure('Bois & Co', 'Bois & Co / F-2 : disque plein');

        $this->assertSame(
            <<<'REPORT'
            Créées : 1, mises à jour : 0, ignorées : 1

            Société Générale : créées 1, mises à jour 0, ignorées 0, en échec 0
            Bricomat : créées 0, mises à jour 0, ignorées 1, en échec 0
            Bois & Co : créées 0, mises à jour 0, ignorées 0, en échec 2

            À ajouter à la main :
            - Bricomat / F-3 : devise USD non prise en charge

            Erreurs, retentées à la prochaine synchro :
            - Bois & Co / F-1 : Qonto indisponible

            Échecs, ignorés jusqu'à la prochaine modification de la facture :
            - Bois & Co / F-2 : disque plein
            REPORT,
            SupplierInvoiceSyncReportFormatter::format($result),
        );
    }
}
