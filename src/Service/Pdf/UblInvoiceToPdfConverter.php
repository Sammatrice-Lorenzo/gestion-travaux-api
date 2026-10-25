<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use App\Exceptions\UnsupportedEInvoiceException;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use XMLReader;

/**
 * Convertit une facture électronique UBL 2.1 (XML pur, format de la réforme de la facturation électronique) en PDF :
 * le PDF joint à la facture s'il y en a un, sinon un récapitulatif A4 généré depuis les données structurées.
 */
final readonly class UblInvoiceToPdfConverter
{
    public const string INVOICE_NAMESPACE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    private const string CAC_NAMESPACE = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const string CBC_NAMESPACE = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    private const string PDF_MIME_TYPE = 'application/pdf';

    private const string PDF_SIGNATURE = '%PDF-';

    private const string DATE_FORMAT = 'd/m/Y';

    private string $tempDir;

    public function __construct(
        #[Autowire('%kernel.cache_dir%/mpdf')]
        ?string $tempDir = null,
    ) {
        $this->tempDir = $tempDir ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf';
    }

    /**
     * Détection sur le contenu : le fichier n'a pas toujours de prologue <?xml (finfo le voit alors en text/plain).
     */
    public function supports(string $content): bool
    {
        return $this->withoutLibxmlErrors(function () use ($content): bool {
            $reader = new XMLReader();
            if (!$reader->XML($content, null, LIBXML_NONET)) {
                return false;
            }

            try {
                // Lecture en flux arrêtée à la racine : inutile d'analyser tout le fichier pour le reconnaître.
                return $this->moveToRootElement($reader) && $this->isUblInvoice($reader->localName, $reader->namespaceURI);
            } finally {
                $reader->close();
            }
        });
    }

    /**
     * @throws UnsupportedEInvoiceException si le XML est illisible, n'est pas une facture UBL ou si le PDF ne peut être généré
     *
     * @return string contenu binaire du PDF
     */
    public function convert(string $content): string
    {
        $xpath = $this->load($content);

        return $this->embeddedPdf($xpath) ?? $this->renderPdf($xpath);
    }

    /**
     * @throws UnsupportedEInvoiceException
     */
    private function load(string $content): DOMXPath
    {
        $document = new DOMDocument();
        // Sans LIBXML_NOENT ni DTD chargée : aucune entité externe n'est résolue (XXE).
        $isLoaded = $this->withoutLibxmlErrors(static fn (): bool => $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT));
        $root = $document->documentElement;

        if (!$isLoaded || null === $root) {
            throw new UnsupportedEInvoiceException('XML illisible');
        }

        if (!$this->isUblInvoice($root->localName, $root->namespaceURI)) {
            throw new UnsupportedEInvoiceException("élément racine « {$root->localName} » inattendu, facture UBL attendue");
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('inv', self::INVOICE_NAMESPACE);
        $xpath->registerNamespace('cac', self::CAC_NAMESPACE);
        $xpath->registerNamespace('cbc', self::CBC_NAMESPACE);

        return $xpath;
    }

    /**
     * Avance jusqu'au premier élément, en sautant prologue, commentaires et instructions de traitement.
     *
     * @return bool false si le contenu ne contient aucun élément (ou n'est pas du XML)
     */
    private function moveToRootElement(XMLReader $reader): bool
    {
        do {
            $hasNode = $reader->read();
        } while ($hasNode && XMLReader::ELEMENT !== $reader->nodeType);

        return $hasNode;
    }

    private function isUblInvoice(?string $localName, ?string $namespace): bool
    {
        return 'Invoice' === $localName && self::INVOICE_NAMESPACE === $namespace;
    }

    /**
     * Un contenu non XML ferait émettre des warnings par libxml : ils sont capturés puis vidés, l'état précédent est rétabli.
     *
     * @template T
     *
     * @param callable(): T $parse
     *
     * @return T
     */
    private function withoutLibxmlErrors(callable $parse): mixed
    {
        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            return $parse();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    /**
     * La plupart des émetteurs joignent la facture « lisible » en base64 : elle est reprise telle quelle.
     */
    private function embeddedPdf(DOMXPath $xpath): ?string
    {
        $objects = $xpath->query('/inv:Invoice/cac:AdditionalDocumentReference/cac:Attachment/cbc:EmbeddedDocumentBinaryObject');

        foreach (false === $objects ? [] : $objects as $object) {
            if (!$object instanceof DOMElement) {
                continue;
            }

            $declared = strtolower(trim($object->getAttribute('mimeCode')));
            $decoded = base64_decode((string) preg_replace('/\s+/', '', $object->textContent), true);
            if (false === $decoded || !str_starts_with($decoded, self::PDF_SIGNATURE)) {
                continue;
            }

            if ('' === $declared || self::PDF_MIME_TYPE === $declared) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Récapitulatif lisible (parties, lignes, totaux) ; le XML d'origine reste la facture qui fait foi.
     *
     * @throws UnsupportedEInvoiceException
     */
    private function renderPdf(DOMXPath $xpath): string
    {
        $invoice = $xpath->document->documentElement ?? throw new UnsupportedEInvoiceException('facture UBL vide');
        $currency = $this->text($xpath, 'cbc:DocumentCurrencyCode', $invoice) ?? 'EUR';
        $number = $this->text($xpath, 'cbc:ID', $invoice) ?? '';
        $dueDate = $this->text($xpath, 'cbc:DueDate', $invoice);

        $html = <<<'EOD'
<style>
                body { font-family: dejavusans; font-size: 9pt; color: #222; }
                h1 { font-size: 16pt; margin: 0 0 4mm 0; }
                table { width: 100%; border-collapse: collapse; }
                .parties td { vertical-align: top; width: 50%; padding: 0 4mm 4mm 0; }
                .label { color: #666; font-size: 8pt; text-transform: uppercase; }
                .lines th { background: #eee; text-align: left; padding: 1.5mm; border-bottom: 0.3mm solid #999; }
                .lines td { padding: 1.5mm; border-bottom: 0.1mm solid #ddd; }
                .num { text-align: right; white-space: nowrap; }
                .totals { width: 45%; margin-left: 55%; margin-top: 4mm; }
                .totals td { padding: 1mm 1.5mm; }
                .grand td { font-weight: bold; border-top: 0.3mm solid #222; }
                .footer { margin-top: 8mm; color: #666; font-size: 7.5pt; }
            </style>
EOD
            . '<h1>Facture ' . $this->escape($number) . '</h1>'
            . '<p>Date d\'émission : ' . $this->escape($this->date($this->text($xpath, 'cbc:IssueDate', $invoice)))
            . (null !== $dueDate ? ' · Échéance : ' . $this->escape($this->date($dueDate)) : '')
            . '</p>'
            . '<table class="parties"><tr>'
            . '<td>' . $this->partyHtml($xpath, 'Fournisseur', 'cac:AccountingSupplierParty/cac:Party', $invoice) . '</td>'
            . '<td>' . $this->partyHtml($xpath, 'Client', 'cac:AccountingCustomerParty/cac:Party', $invoice) . '</td>'
            . '</tr></table>'
            . $this->linesHtml($xpath, $invoice, $currency)
            . $this->totalsHtml($xpath, $invoice, $currency)
            . $this->notesHtml($xpath, $invoice)
            . '<p class="footer">Document généré automatiquement à partir de la facture électronique UBL reçue ; le fichier XML d\'origine fait foi.</p>';

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'tempDir' => $this->tempDir,
            ]);
            $mpdf->SetTitle('Facture ' . $number);
            $mpdf->WriteHTML($html);

            return $mpdf->OutputBinaryData();
        } catch (MpdfException $exception) {
            throw new UnsupportedEInvoiceException('génération du PDF impossible (' . $exception->getMessage() . ')');
        }
    }

    private function partyHtml(DOMXPath $xpath, string $label, string $path, DOMNode $invoice): string
    {
        $party = $this->node($xpath, $path, $invoice);
        if (null === $party) {
            return '<div class="label">' . $label . '</div>—';
        }

        $name = $this->text($xpath, 'cac:PartyLegalEntity/cbc:RegistrationName', $party) ?? $this->text($xpath, 'cac:PartyName/cbc:Name', $party);
        $street = $this->text($xpath, 'cac:PostalAddress/cbc:StreetName', $party);
        $city = trim(($this->text($xpath, 'cac:PostalAddress/cbc:PostalZone', $party) ?? '') . ' ' . ($this->text($xpath, 'cac:PostalAddress/cbc:CityName', $party) ?? ''));
        $vatNumber = $this->text($xpath, 'cac:PartyTaxScheme/cbc:CompanyID', $party);
        $legalId = $this->text($xpath, 'cac:PartyLegalEntity/cbc:CompanyID', $party);

        $lines = array_filter([
            null !== $name ? '<strong>' . $this->escape($name) . '</strong>' : null,
            null !== $street ? $this->escape($street) : null,
            '' !== $city ? $this->escape($city) : null,
            null !== $vatNumber ? 'TVA : ' . $this->escape($vatNumber) : null,
            null !== $legalId ? 'SIREN/SIRET : ' . $this->escape($legalId) : null,
        ]);

        return '<div class="label">' . $label . '</div>' . implode('<br>', $lines);
    }

    private function linesHtml(DOMXPath $xpath, DOMNode $invoice, string $currency): string
    {
        $lines = $xpath->query('cac:InvoiceLine', $invoice);
        if (false === $lines || 0 === $lines->length) {
            return '';
        }

        $rows = '';
        foreach ($lines as $line) {
            $rows .= '<tr>'
                . '<td>' . $this->escape($this->text($xpath, 'cac:Item/cbc:Name', $line) ?? $this->text($xpath, 'cac:Item/cbc:Description', $line) ?? '') . '</td>'
                . '<td class="num">' . $this->escape($this->text($xpath, 'cbc:InvoicedQuantity', $line) ?? '') . '</td>'
                . '<td class="num">' . $this->escape($this->amount($this->text($xpath, 'cac:Price/cbc:PriceAmount', $line), $currency)) . '</td>'
                . '<td class="num">' . $this->escape($this->percent($this->text($xpath, 'cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', $line))) . '</td>'
                . '<td class="num">' . $this->escape($this->amount($this->text($xpath, 'cbc:LineExtensionAmount', $line), $currency)) . '</td>'
                . '</tr>';
        }

        return '<table class="lines"><thead><tr><th>Désignation</th><th class="num">Qté</th><th class="num">PU HT</th><th class="num">TVA</th><th class="num">Total HT</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    private function totalsHtml(DOMXPath $xpath, DOMNode $invoice, string $currency): string
    {
        $rows = [
            'Total HT' => $this->text($xpath, 'cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', $invoice),
            'TVA' => $this->text($xpath, 'cac:TaxTotal/cbc:TaxAmount', $invoice),
            'Total TTC' => $this->text($xpath, 'cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', $invoice),
        ];
        $payable = $this->text($xpath, 'cac:LegalMonetaryTotal/cbc:PayableAmount', $invoice);

        $html = '';
        foreach (array_filter($rows, static fn (?string $value): bool => null !== $value) as $label => $value) {
            $html .= '<tr><td>' . $label . '</td><td class="num">' . $this->escape($this->amount($value, $currency)) . '</td></tr>';
        }

        if (null !== $payable) {
            $html .= '<tr class="grand"><td>Net à payer</td><td class="num">' . $this->escape($this->amount($payable, $currency)) . '</td></tr>';
        }

        return '' === $html ? '' : '<table class="totals">' . $html . '</table>';
    }

    private function notesHtml(DOMXPath $xpath, DOMNode $invoice): string
    {
        $notes = $xpath->query('cbc:Note', $invoice);
        if (false === $notes || 0 === $notes->length) {
            return '';
        }

        $html = '';
        foreach ($notes as $note) {
            $text = trim($note->textContent);
            if ('' !== $text) {
                $html .= '<p>' . nl2br($this->escape($text)) . '</p>';
            }
        }

        return '' === $html ? '' : '<div class="label" style="margin-top: 6mm;">Notes</div>' . $html;
    }

    private function node(DOMXPath $xpath, string $path, DOMNode $context): ?DOMNode
    {
        $nodes = $xpath->query($path, $context);

        return false === $nodes ? null : $nodes->item(0);
    }

    private function text(DOMXPath $xpath, string $path, ?DOMNode $context): ?string
    {
        if (null === $context) {
            return null;
        }

        $value = trim((string) $this->node($xpath, $path, $context)?->textContent);

        return '' === $value ? null : $value;
    }

    private function amount(?string $value, string $currency): string
    {
        if (null === $value) {
            return '';
        }

        return is_numeric($value) ? number_format((float) $value, 2, ',', ' ') . ' ' . $currency : $value;
    }

    private function percent(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        return is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') . ' %' : $value;
    }

    private function date(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $date ? $value : $date->format(self::DATE_FORMAT);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
