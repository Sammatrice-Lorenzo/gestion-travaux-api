<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class OllamaService
{
    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $parameterBagInterface,
    ) {}

    public function ask(string $prompt, string $model = 'mistral:7b'): string
    {
        /** @var string $baseUrl */
        $baseUrl = $this->parameterBagInterface->get('url_api_ollama');

        $response = $this->client->request('POST', "{$baseUrl}api/generate", [
            'json' => [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ],
            'timeout' => 600,
        ]);

        $data = json_decode($response->getContent(), true);

        if (!isset($data['response'])) {
            throw new \RuntimeException('Invalid Ollama response: ' . $response->getContent(false));
        }

        return $data['response'];
    }
}
