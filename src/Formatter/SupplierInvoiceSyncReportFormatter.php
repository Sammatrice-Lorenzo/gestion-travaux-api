<?php

declare(strict_types=1);

namespace App\Formatter;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;

/**
 * Bilan d'une synchro en texte : le tableau de bord Messenger affiche un texte tel quel, alors qu'il encoderait
 * un tableau en JSON, ce qui rendrait les accents illisibles.
 */
final class SupplierInvoiceSyncReportFormatter
{
    public static function format(SupplierInvoiceSyncResult $result): string
    {
        $sections = [
            "Créées : {$result->created()}, mises à jour : {$result->updated()}, ignorées : {$result->skipped()}",
            self::supplierLines($result),
            self::listSection('À ajouter à la main :', $result->unsupported()),
            self::listSection('Erreurs, retentées à la prochaine synchro :', $result->errors()),
            self::listSection('Échecs, ignorés jusqu\'à la prochaine modification de la facture :', $result->failures()),
        ];

        return implode("\n\n", array_filter($sections, static fn (string $section): bool => '' !== $section));
    }

    private static function supplierLines(SupplierInvoiceSyncResult $result): string
    {
        $lines = [];
        foreach ($result->suppliers() as $supplier => $counts) {
            $lines[] = "{$supplier} : créées {$counts['created']}, mises à jour {$counts['updated']}, ignorées {$counts['skipped']}, en échec {$counts['failed']}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $items
     */
    private static function listSection(string $title, array $items): string
    {
        return [] === $items ? '' : $title . "\n- " . implode("\n- ", $items);
    }
}
