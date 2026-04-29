<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;

final class AuthOpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $openApi = $this->addSecurityScheme($openApi);
        $this->addLoginPath($openApi);
        $this->addLogoutPath($openApi);
        $this->addRefreshPath($openApi);

        return $openApi;
    }

    private function addSecurityScheme(OpenApi $openApi): OpenApi
    {
        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?? new \ArrayObject();
        $securitySchemes['cookieAuth'] = new SecurityScheme(
            type: 'apiKey',
            in: 'cookie',
            name: 'BEARER',
            description: 'JWT stocké en cookie HttpOnly',
        );

        $components = $openApi->getComponents()->withSecuritySchemes($securitySchemes);

        return $openApi->withComponents($components);
    }

    private function addLoginPath(OpenApi $openApi): void
    {
        $loginOperation = (new Operation(
            operationId: 'postApiAuthLogin',
            tags: ['Auth'],
            summary: 'Authentification — obtenir un cookie JWT',
            requestBody: new RequestBody(
                required: true,
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['email', 'password'],
                            'properties' => [
                                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@money-manager.local'],
                                'password' => ['type' => 'string', 'example' => 'password'],
                            ],
                        ],
                    ],
                ]),
            ),
        ))
            ->addResponse(new Response(description: 'Cookie BEARER posé'), 200)
            ->addResponse(new Response(description: 'Identifiants invalides'), 401)
            ->addResponse(new Response(description: 'Trop de tentatives — réessayez dans 60 secondes'), 429);

        $openApi->getPaths()->addPath('/api/auth/login', new PathItem(post: $loginOperation));
    }

    private function addLogoutPath(OpenApi $openApi): void
    {
        $logoutOperation = (new Operation(
            operationId: 'postApiAuthLogout',
            tags: ['Auth'],
            summary: 'Déconnexion — effacer le cookie JWT',
            security: [],
        ))
            ->addResponse(new Response(description: 'Cookie BEARER effacé'), 200);

        $openApi->getPaths()->addPath('/api/auth/logout', new PathItem(post: $logoutOperation));
    }

    private function addRefreshPath(OpenApi $openApi): void
    {
        $refreshOperation = (new Operation(
            operationId: 'postApiAuthRefresh',
            tags: ['Auth'],
            summary: 'Renouvellement de session — rotation du refresh token',
            security: [],
        ))
            ->addResponse(new Response(description: 'Nouveaux cookies BEARER et REFRESH_TOKEN posés'), 200)
            ->addResponse(new Response(description: 'Refresh token absent, invalide ou révoqué'), 401);

        $openApi->getPaths()->addPath('/api/auth/refresh', new PathItem(post: $refreshOperation));
    }
}
