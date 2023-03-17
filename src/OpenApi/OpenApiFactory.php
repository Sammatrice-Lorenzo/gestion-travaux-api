<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Repository\UserRepository;
use ArrayObject;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly UserRepository $userRepo,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Credentials'] = $this->getCredential();

        $schemas['cookieAuth'] = new ArrayObject([
            'type' => 'apiKey',
            'in' => 'cookie',
            'name' => 'PHPSESSID'
        ]);

        $openApi->getPaths()->addPath('/api/login', $this->getLoginPath());
        $openApi->getPaths()->addPath('/api/logout', $this->getLogoutPath());

        return $openApi;
    }

    private function getCredential(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'exemple' => 'test@test.com',
                ],
                'password' => [
                    'type' => 'string',
                    'exemple' => '0000',
                ]
            ]
        ]);
    }

    private function getLogoutPath(): PathItem
    {
        return new PathItem(
            post: new Operation(
                operationId: 'postApiLogout',
                tags: ['Auth'],
                summary: 'Déconnexion utilisateur connecté',
                responses: [
                    '204' => [
                        'description' => 'Utilisateur deconnecté',
                        'content' => 'No content'
                    ]
                ]
            )
        );
    }

    private function getLoginPath(): PathItem
    {
        return new PathItem(
            post: new Operation(
                operationId: 'postApiLogin',
                tags: ['Auth'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials'
                            ]
                        ]
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'Utilisateur connecté',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/User-read.User'
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );
    }
}
