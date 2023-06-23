<?php

namespace App\OpenApi;

use ArrayObject;
use ApiPlatform\OpenApi\OpenApi;
use App\Repository\UserRepository;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;

readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private UserRepository $userRepo,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ]);

        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Token'] = $this->getCredential();
        $schemas['Link'] = $this->getLinkForVerifiedEmail();
        $schemas['Registration'] = $this->getUserRegistration();

        $openApi->getPaths()->addPath('/api/register', $this->getRegistrationPath());
        $openApi->getPaths()->addPath('/api/login', $this->getLoginPath());
        $openApi->getPaths()->addPath('/api/logout', $this->getLogoutPath());
        $openApi->getPaths()->addPath('/verify/email', $this->getVerifiedToken());

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
            ]
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
            ]
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
                            ]
                        ]
                    ])
                ),
                responses: [
                    '204' => [
                        'description' => 'Utilisateur vérifiée',
                        'content' => 'No content'
                    ]
                ]
            )
        );
    }

    private function getUserRegistration(): ArrayObject
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
                'password' => [
                    'type' => 'string',
                    'exemple' => '0000',
                ],
                'confirmPassword' => [
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
                        'description' => 'Utilisateur déconnecté',
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
                        'description' => 'Token JWT',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token'
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );
    }

    private function getRegistrationPath(): PathItem
    {
        return new PathItem(
            post: new Operation(
                operationId: 'postApiRegistration',
                tags: ['Auth'],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Registration'
                            ]
                        ]
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'Utilisateur enregistrée',
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
