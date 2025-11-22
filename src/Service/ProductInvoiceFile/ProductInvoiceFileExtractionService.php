<?php

namespace App\Service\ProductInvoiceFile;

use App\Service\OllamaService;
use App\Dto\ProductInvoiceResponseIA;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class ProductInvoiceFileExtractionService
{
    public function __construct(
        private OllamaService $ollama,
        private SerializerInterface $serializerInterface,
        private ParameterBagInterface $parameterBagInterface
    ) {}

    public function extractInvoiceData(string $text): ProductInvoiceResponseIA
    {
        /** @var string $pathPromptProductInvoiceFile */
        $pathPromptProductInvoiceFile = $this->parameterBagInterface->get('promts') . 'ProductInvoicePrompt.md';
        $promptProductInvoiceFile = file_get_contents($pathPromptProductInvoiceFile);

        $prompt = <<<PROMPT
            {$promptProductInvoiceFile}
            {$text}
        PROMPT;

        $response = $this->ollama->ask($prompt);

        return $this->serializerInterface->deserialize(
            $response,
            ProductInvoiceResponseIA::class,
            'json'
        );
    }
}
