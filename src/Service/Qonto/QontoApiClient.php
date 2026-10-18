<?php

declare(strict_types=1);

namespace App\Service\Qonto;

use App\Dto\Qonto\QontoAttachment;
use App\Dto\Qonto\QontoSupplierInvoicePage;
use App\Exceptions\QontoApiException;
use App\Interface\Qonto\QontoSupplierInvoiceClientInterface;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class QontoApiClient implements QontoSupplierInvoiceClientInterface
{
    public const int PER_PAGE = 100;

    private const string DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    /**
     * @param HttpClientInterface $qontoClient client « qonto.client » (base_uri, Authorization et réessais dans framework.yaml)
     * @param HttpClientInterface $httpClient  client par défaut, pour les liens de téléchargement signés hors API Qonto
     */
    public function __construct(
        private HttpClientInterface $qontoClient,
        private HttpClientInterface $httpClient,
        #[Autowire('%env(QONTO_STAGING_TOKEN)%')]
        private string $stagingToken,
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

        return QontoSupplierInvoicePage::fromArray($response);
    }

    public function getAttachment(string $attachmentId): QontoAttachment
    {
        return QontoAttachment::fromArray($this->getJson('/v2/attachments/' . rawurlencode($attachmentId)));
    }

    public function downloadFile(string $url): string
    {
        return $this->send(fn (): ResponseInterface => $this->httpClient->request('GET', $url))->getContent();
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

    private function formatDate(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(self::DATE_FORMAT);
    }
}
