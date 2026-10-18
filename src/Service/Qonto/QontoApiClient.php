<?php

declare(strict_types=1);

namespace App\Service\Qonto;

use App\Dto\Qonto\QontoAttachment;
use App\Dto\Qonto\QontoSupplierInvoice;
use App\Dto\Qonto\QontoSupplierInvoicePage;
use App\Exceptions\QontoApiException;
use App\Interface\Qonto\QontoSupplierInvoiceClientInterface;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class QontoApiClient implements QontoSupplierInvoiceClientInterface
{
    public const int PER_PAGE = 100;

    public const int MAX_DOWNLOAD_BYTES = 20 * 1024 * 1024;

    private const string DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    /**
     * @param HttpClientInterface $qontoClient         client « qonto.client » (base_uri, Authorization et réessais dans framework.yaml)
     * @param HttpClientInterface $qontoDownloadClient client « qonto.download.client » pour les liens signés hors API Qonto (sans Authorization, avec réessais et timeouts)
     */
    public function __construct(
        private HttpClientInterface $qontoClient,
        private HttpClientInterface $qontoDownloadClient,
        #[Autowire('%env(QONTO_STAGING_TOKEN)%')]
        private string $stagingToken,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function listSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo, int $page = 1): QontoSupplierInvoicePage
    {
        $response = $this->getJson('/v2/supplier_invoices', [
            'filter' => [
                'updated_at_from' => $this->formatDate($updatedFrom),
                'updated_at_to' => $this->formatDate($updatedTo),
                'exclude_credit_notes' => 'true',
            ],
            'sort_by' => 'updated_at:asc',
            'per_page' => self::PER_PAGE,
            'page' => $page,
        ]);

        $invoicePage = QontoSupplierInvoicePage::fromArray($response);
        if ($invoicePage->skippedCount > 0) {
            $this->logger->warning('Qonto : factures fournisseurs sans identifiant ignorées.', [
                'skipped' => $invoicePage->skippedCount,
                'page' => $page,
                'updated_at_from' => $this->formatDate($updatedFrom),
                'updated_at_to' => $this->formatDate($updatedTo),
            ]);
        }

        return $invoicePage;
    }

    public function iterateSupplierInvoices(DateTimeImmutable $updatedFrom, DateTimeImmutable $updatedTo): iterable
    {
        return $this->iteratePages($updatedFrom, $updatedTo, 1, []);
    }

    public function getAttachment(string $attachmentId): QontoAttachment
    {
        return QontoAttachment::fromArray($this->getJson('/v2/attachments/' . rawurlencode($attachmentId)));
    }

    public function downloadFile(string $url): string
    {
        $response = $this->send(fn (): ResponseInterface => $this->qontoDownloadClient->request('GET', $url, ['buffer' => false]));

        $content = '';

        try {
            foreach ($this->qontoDownloadClient->stream($response) as $chunk) {
                $content .= $chunk->getContent();
                if (\strlen($content) > self::MAX_DOWNLOAD_BYTES) {
                    $response->cancel();

                    throw new QontoApiException('Qonto : pièce jointe trop volumineuse (plus de ' . self::MAX_DOWNLOAD_BYTES . ' octets).');
                }
            }
        } catch (ExceptionInterface $exception) {
            throw new QontoApiException('Qonto : téléchargement interrompu (' . $exception->getMessage() . ').', previous: $exception);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function getJson(string $path, array $query = []): array
    {
        $headers = '' !== $this->stagingToken ? ['X-Qonto-Staging-Token' => $this->stagingToken] : [];

        $response = $this->send(fn (): ResponseInterface => $this->qontoClient->request('GET', $path, [
            'query' => $query,
            'headers' => $headers,
        ]));

        try {
            /** @var array<string, mixed> */
            return $response->toArray();
        } catch (ExceptionInterface $exception) {
            throw new QontoApiException('Qonto : réponse JSON invalide.', previous: $exception);
        }
    }

    /**
     * Exécute la requête et force la lecture du statut pour convertir les erreurs HTTP en QontoApiException.
     *
     * @param callable(): ResponseInterface $request
     */
    private function send(callable $request): ResponseInterface
    {
        try {
            $response = $request();
            $statusCode = $response->getStatusCode();
        } catch (ExceptionInterface $exception) {
            throw new QontoApiException('Qonto : erreur réseau (' . $exception->getMessage() . ').', previous: $exception);
        }

        if ($statusCode >= 300) {
            throw QontoApiException::fromStatusCode($statusCode);
        }

        return $response;
    }

    /**
     * Renvoie les factures de la page demandée (indexées par id, sans doublon), puis enchaîne récursivement sur la position suivante.
     *
     * @param array<string, true> $seenIds
     *
     * @return iterable<string, QontoSupplierInvoice>
     */
    private function iteratePages(DateTimeImmutable $cursor, DateTimeImmutable $updatedTo, int $page, array $seenIds): iterable
    {
        $invoicePage = $this->listSupplierInvoices($cursor, $updatedTo, $page);

        foreach ($invoicePage->invoices as $invoice) {
            if (!isset($seenIds[$invoice->id])) {
                $seenIds[$invoice->id] = true;

                yield $invoice->id => $invoice;
            }
        }

        if (null !== $invoicePage->meta->nextPage) {
            [$nextCursor, $nextPage] = $this->nextPosition($invoicePage, $cursor, $page);

            yield from $this->iteratePages($nextCursor, $updatedTo, $nextPage, $seenIds);
        }
    }

    /**
     * Repart du dernier updated_at de la page (moins 1 ms) en page 1 ; si le curseur n'avance pas, passe à la page suivante.
     *
     * @return array{DateTimeImmutable, int}
     */
    private function nextPosition(QontoSupplierInvoicePage $invoicePage, DateTimeImmutable $cursor, int $page): array
    {
        $updatedAts = array_filter(array_map(
            static fn (QontoSupplierInvoice $invoice): ?DateTimeImmutable => $invoice->updatedAt,
            $invoicePage->invoices,
        ));
        $nextCursor = [] === $updatedAts ? null : max($updatedAts)->modify('-1 millisecond');

        return null !== $nextCursor && $nextCursor > $cursor ? [$nextCursor, 1] : [$cursor, $page + 1];
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(self::DATE_FORMAT);
    }
}
