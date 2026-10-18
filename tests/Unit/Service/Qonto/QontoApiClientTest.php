<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Qonto;

use App\Exceptions\QontoApiException;
use App\Service\Qonto\QontoApiClient;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class QontoApiClientTest extends Unit
{
    private const string BASE_URL = 'https://thirdparty.qonto.com';

    private const string ATTACHMENT_URL = 'https://qonto-bucket.s3.amazonaws.com/file?X-Amz-Signature=abc';

    public function testListSupplierInvoicesSendsFiltersAndParsesPage(): void
    {
        $requests = [];
        $client = $this->createClient(new MockHttpClient(
            function (string $method, string $url, array $options) use (&$requests): MockResponse {
                $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

                return $this->jsonResponse([
                    'supplier_invoices' => [$this->invoicePayload()],
                    'meta' => ['current_page' => 1, 'next_page' => 2, 'prev_page' => null, 'total_pages' => 2, 'total_count' => 150, 'per_page' => 100],
                ]);
            },
            self::BASE_URL,
        ));

        $page = $client->listSupplierInvoices(
            new DateTimeImmutable('2026-01-01T00:00:00+01:00'),
            new DateTimeImmutable('2026-01-31T12:00:00Z'),
            1,
        );

        $this->assertCount(1, $requests);
        $this->assertSame('GET', $requests[0]['method']);

        $query = $this->parseQuery($requests[0]['url']);
        $this->assertStringStartsWith(self::BASE_URL . '/v2/supplier_invoices?', $requests[0]['url']);
        $this->assertSame('2025-12-31T23:00:00.000Z', $query['filter']['updated_at_from']);
        $this->assertSame('2026-01-31T12:00:00.000Z', $query['filter']['updated_at_to']);
        $this->assertSame('true', $query['filter']['exclude_credit_notes']);
        $this->assertSame('updated_at:asc', $query['sort_by']);
        $this->assertSame('100', $query['per_page']);
        $this->assertSame('1', $query['page']);
        $this->assertArrayNotHasKey('x-qonto-staging-token', $requests[0]['options']['normalized_headers']);

        $this->assertSame(2, $page->meta->nextPage);
        $this->assertSame(150, $page->meta->totalCount);

        $invoice = $page->invoices[0];
        $this->assertSame('inv-1', $invoice->id);
        $this->assertSame('F-2026-001', $invoice->invoiceNumber);
        $this->assertSame('att-1', $invoice->attachmentId);
        $this->assertSame(120.5, $invoice->totalAmount);
        $this->assertSame('EUR', $invoice->currency);
        $this->assertSame('2026-01-15', $invoice->issueDate?->format('Y-m-d'));
        $this->assertSame('sup-1', $invoice->supplierId);
        $this->assertSame('Bricoman', $invoice->supplierName);
        $this->assertSame('FR32123456789', $invoice->vatNumber);
        $this->assertSame('Paris', $invoice->supplierCity);
        $this->assertFalse($invoice->isCreditNote);
    }

    public function testLastPageHasNoNextPage(): void
    {
        $client = $this->createClient(new MockHttpClient($this->jsonResponse([
            'supplier_invoices' => [],
            'meta' => ['current_page' => 2, 'next_page' => null, 'prev_page' => 1, 'total_pages' => 2, 'total_count' => 100, 'per_page' => 100],
        ]), self::BASE_URL));

        $page = $client->listSupplierInvoices(new DateTimeImmutable('-1 day'), new DateTimeImmutable(), 2);

        $this->assertSame([], $page->invoices);
        $this->assertNull($page->meta->nextPage);
    }

    public function testStagingTokenHeaderIsSentWhenConfigured(): void
    {
        $headers = [];
        $client = $this->createClient(new MockHttpClient(
            function (string $method, string $url, array $options) use (&$headers): MockResponse {
                $headers = $options['normalized_headers'];

                return $this->jsonResponse(['attachment' => ['id' => 'att-1']]);
            },
            self::BASE_URL,
        ), stagingToken: 'staging-123');

        $client->getAttachment('att-1');

        $this->assertSame(['X-Qonto-Staging-Token: staging-123'], $headers['x-qonto-staging-token']);
    }

    public function testGetAttachment(): void
    {
        $url = null;
        $client = $this->createClient(new MockHttpClient(
            function (string $method, string $requestUrl) use (&$url): MockResponse {
                $url = $requestUrl;

                return $this->jsonResponse(['attachment' => [
                    'id' => 'att-1',
                    'file_name' => 'ticket.jpg',
                    'file_size' => '666',
                    'file_content_type' => 'image/jpeg',
                    'url' => self::ATTACHMENT_URL,
                ]]);
            },
            self::BASE_URL,
        ));

        $attachment = $client->getAttachment('att-1');

        $this->assertSame(self::BASE_URL . '/v2/attachments/att-1', $url);
        $this->assertSame('ticket.jpg', $attachment->fileName);
        $this->assertSame('image/jpeg', $attachment->fileContentType);
        $this->assertSame(self::ATTACHMENT_URL, $attachment->url);
    }

    public function testDownloadFileUsesDefaultClientWithoutQontoHeaders(): void
    {
        $qontoClient = new MockHttpClient(function (): never {
            $this->fail('Le lien signé ne doit pas passer par le client Qonto.');
        }, self::BASE_URL);
        $downloadOptions = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$downloadOptions): MockResponse {
            $downloadOptions = $options;

            return new MockResponse('%PDF-1.4 content');
        });

        $client = new QontoApiClient($qontoClient, $httpClient, '');

        $this->assertSame('%PDF-1.4 content', $client->downloadFile(self::ATTACHMENT_URL));
        $this->assertArrayNotHasKey('authorization', $downloadOptions['normalized_headers']);
    }

    public function testUnauthorizedThrowsQontoApiException(): void
    {
        $client = $this->createClient(new MockHttpClient(
            new MockResponse('{"errors":[{"code":"unauthorized"}]}', ['http_code' => 401]),
            self::BASE_URL,
        ));

        $this->expectException(QontoApiException::class);
        $this->expectExceptionCode(401);

        $client->listSupplierInvoices(new DateTimeImmutable('-1 day'), new DateTimeImmutable());
    }

    public function testTooManyRequestsThrowsQontoApiException(): void
    {
        $client = $this->createClient(new MockHttpClient(
            new MockResponse('{"errors":[]}', ['http_code' => 429]),
            self::BASE_URL,
        ));

        try {
            $client->getAttachment('att-1');
            $this->fail('Une QontoApiException était attendue.');
        } catch (QontoApiException $exception) {
            $this->assertSame(429, $exception->getCode());
            $this->assertStringContainsString('trop de requêtes', $exception->getMessage());
        }
    }

    public function testDownloadErrorThrowsQontoApiException(): void
    {
        $client = new QontoApiClient(
            new MockHttpClient([], self::BASE_URL),
            new MockHttpClient(new MockResponse('expired', ['http_code' => 403])),
            '',
        );

        $this->expectException(QontoApiException::class);
        $this->expectExceptionCode(403);

        $client->downloadFile(self::ATTACHMENT_URL);
    }

    private function createClient(MockHttpClient $qontoClient, string $stagingToken = ''): QontoApiClient
    {
        return new QontoApiClient($qontoClient, new MockHttpClient(), $stagingToken);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function jsonResponse(array $body): MockResponse
    {
        return new MockResponse((string) json_encode($body), [
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseQuery(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(): array
    {
        return [
            'id' => 'inv-1',
            'status' => 'to_pay',
            'organization_id' => 'org-1',
            'source_type' => 'email',
            'attachment_id' => 'att-1',
            'display_attachment_id' => 'att-1',
            'created_at' => '2026-01-15T10:00:00.000Z',
            'updated_at' => '2026-01-16T10:00:00.000Z',
            'file_name' => 'facture.pdf',
            'declined_note' => null,
            'is_einvoice' => true,
            'has_discrepancies' => false,
            'supplier_id' => 'sup-1',
            'supplier_name' => 'Bricoman',
            'invoice_number' => 'F-2026-001',
            'total_amount' => ['value' => '120.50', 'currency' => 'EUR'],
            'issue_date' => '2026-01-15',
            'is_credit_note' => false,
            'supplier_snapshot' => ['id' => 'sup-1', 'name' => 'Bricoman', 'vat_number' => 'FR32123456789'],
            'seller' => [
                'name' => 'Bricoman SAS',
                'address' => ['street_address' => '1 rue du Test', 'zip_code' => '75001', 'city' => 'Paris', 'country_code' => 'FR'],
            ],
        ];
    }
}
