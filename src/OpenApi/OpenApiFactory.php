<?php

namespace App\OpenApi;

use ArrayObject;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Token'] = $this->getCredential();
        $schemas['Link'] = $this->getLinkForVerifiedEmail();
        $schemas['EditUser'] = $this->getEditUser();

        $openApi->getPaths()->addPath('/api/login', $this->getLoginPath());
        $openApi->getPaths()->addPath('/api/logout', $this->getLogoutPath());
        $openApi->getPaths()->addPath('/verify/email', $this->getVerifiedToken());
        $openApi->getPaths()->addPath('/api/invoice-file', $this->getInvoiceFile());

        return $openApi;
    }

    private function getCredential(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }

    private function getLinkForVerifiedEmail(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                ],
                'link' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }

    public function getVerifiedToken(): PathItem
    {
        return new PathItem(
            post: new Operation(
                operationId: 'postVerifiedEmail',
                tags: ['Auth'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Link',
                            ],
                        ],
                    ])
                ),
                responses: [
                    '204' => [
                        'description' => 'Utilisateur vérifiée',
                        'content' => 'No content',
                    ],
                ]
            )
        );
    }

    public function getInvoiceFile(): PathItem
    {
        return new PathItem(
            post: new Operation(
                operationId: 'postInvoiceFile',
                tags: ['File'],
            )
        );
    }

    private function getEditUser(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'properties' => [
                'firstname' => [
                    'type' => 'string',
                    'exemple' => 'Jules',
                ],
                'lastname' => [
                    'type' => 'string',
                    'exemple' => 'Du Pont',
                ],
                'email' => [
                    'type' => 'string',
                    'exemple' => 'test@test.com',
                ],
            ],
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
                        'description' => 'Utilisateur déconnecté',
                        'content' => 'No content',
                    ],
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
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'Token JWT',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );
    }
}
