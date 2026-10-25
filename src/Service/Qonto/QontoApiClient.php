<?php

declare(strict_types=1);

namespace App\Service\Qonto;

use App\Dto\InvoicingPlatform\PlatformAttachment;
use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Dto\Qonto\QontoSupplierInvoicePage;
use App\Exceptions\QontoApiException;
use App\Interface\InvoicingPlatform\InvoicingPlatformClientInterface;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class QontoApiClient implements InvoicingPlatformClientInterface
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
        private QontoPayloadMapper $mapper = new QontoPayloadMapper(),
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Une page de factures fournisseurs (hors avoirs) triées par updated_at croissant. Détail propre à Qonto :
     * le code métier passe par iterateSupplierInvoices(), qui gère une pagination stable.
     *
     * @throws QontoApiException
     */
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

        $invoicePage = $this->mapper->toSupplierInvoicePage($response);
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

    public function getAttachment(string $attachmentId): PlatformAttachment
    {
        return $this->mapper->toAttachment($this->getJson('/v2/attachments/' . rawurlencode($attachmentId)));
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
     * Mémoire bornée quel que soit le nombre de pages : la page est libérée avant l'appel suivant, et seuls les ids
     * encore susceptibles de revenir (updated_at ≥ prochain curseur) sont transmis.
     *
     * @param array<string, null|DateTimeImmutable> $seenIds id => updated_at des factures déjà renvoyées
     *
     * @return iterable<string, PlatformSupplierInvoice>
     */
    private function iteratePages(DateTimeImmutable $cursor, DateTimeImmutable $updatedTo, int $page, array $seenIds): iterable
    {
        $invoicePage = $this->listSupplierInvoices($cursor, $updatedTo, $page);

        foreach ($invoicePage->invoices as $invoice) {
            if (!\array_key_exists($invoice->id, $seenIds)) {
                $seenIds[$invoice->id] = $invoice->updatedAt;

                yield $invoice->id => $invoice;
            }
        }

        if (null === $invoicePage->meta->nextPage) {
            return;
        }

        [$nextCursor, $nextPage] = $this->nextPosition($invoicePage, $cursor, $page);
        unset($invoicePage);
        $seenIds = array_filter(
            $seenIds,
            static fn (?DateTimeImmutable $updatedAt): bool => null !== $updatedAt && $updatedAt >= $nextCursor,
        );

        yield from $this->iteratePages($nextCursor, $updatedTo, $nextPage, $seenIds);
    }

    /**
     * Repart du dernier updated_at de la page (moins 1 ms) en page 1 ; si le curseur n'avance pas, passe à la page suivante.
     *
     * @return array{DateTimeImmutable, int}
     */
    private function nextPosition(QontoSupplierInvoicePage $invoicePage, DateTimeImmutable $cursor, int $page): array
    {
        $updatedAts = array_filter(array_map(
            static fn (PlatformSupplierInvoice $invoice): ?DateTimeImmutable => $invoice->updatedAt,
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
