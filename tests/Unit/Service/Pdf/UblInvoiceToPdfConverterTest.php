<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Pdf;

use App\Exceptions\UnsupportedEInvoiceException;
use App\Service\Pdf\UblInvoiceToPdfConverter;
use Codeception\Test\Unit;
use Smalot\PdfParser\Parser;

final class UblInvoiceToPdfConverterTest extends Unit
{
    private UblInvoiceToPdfConverter $converter;

    protected function _before(): void
    {
        $this->converter = new UblInvoiceToPdfConverter();
    }

    public function testSupportsUblInvoiceWithoutXmlProlog(): void
    {
        $this->assertTrue($this->converter->supports($this->ublInvoice()));
        $this->assertTrue($this->converter->supports('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $this->ublInvoice()));
        $this->assertTrue($this->converter->supports('<Invoice xmlns="' . UblInvoiceToPdfConverter::INVOICE_NAMESPACE . '"><ID xmlns="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">1</ID></Invoice>'));
    }

    public function testDoesNotSupportOtherContents(): void
    {
        $this->assertFalse($this->converter->supports($this->pdfContent()));
        $this->assertFalse($this->converter->supports('Bonjour, ceci est un simple texte.'));
        $this->assertFalse($this->converter->supports('<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"/>'));
        $this->assertFalse($this->converter->supports('<Invoice><ID>sans namespace UBL</ID></Invoice>'));
    }

    public function testEmbeddedPdfIsReturnedAsIs(): void
    {
        $pdf = $this->pdfContent();
        $attachment = '<cac:AdditionalDocumentReference><cbc:ID>facture</cbc:ID><cac:Attachment>'
            . '<cbc:EmbeddedDocumentBinaryObject mimeCode="application/pdf" filename="facture.pdf">'
            . chunk_split(base64_encode($pdf), 76, "\n")
            . '</cbc:EmbeddedDocumentBinaryObject></cac:Attachment></cac:AdditionalDocumentReference>';

        $xml = str_replace('<cac:AccountingSupplierParty>', $attachment . '<cac:AccountingSupplierParty>', $this->ublInvoice());

        $this->assertSame($pdf, $this->converter->convert($xml));
    }

    public function testPdfIsGeneratedFromStructuredData(): void
    {
        $pdf = $this->converter->convert($this->ublInvoice());

        $this->assertStringStartsWith('%PDF-', $pdf);
        $text = (new Parser())->parseContent($pdf)->getText();
        $this->assertStringContainsString('UBL-2026-0042', $text);
        $this->assertStringContainsString('Fournisseur UBL SAS', $text);
        $this->assertStringContainsString('Sac de ciment 35 kg', $text);
        $this->assertStringContainsString('12/09/2026', $text);
        $this->assertStringContainsString('120,00 EUR', $text);
    }

    public function testInvalidXmlThrows(): void
    {
        $this->expectException(UnsupportedEInvoiceException::class);

        $this->converter->convert('<ubl:Invoice xmlns:ubl="' . UblInvoiceToPdfConverter::INVOICE_NAMESPACE . '"><non-fermé>');
    }

    private function ublInvoice(): string
    {
        return (string) file_get_contents(codecept_data_dir('ubl-invoice.xml'));
    }

    private function pdfContent(): string
    {
        return (string) file_get_contents(codecept_data_dir('InvoiceTemplate.pdf'));
    }
}
