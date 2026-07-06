<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AlbSimulatorMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AlbSimulatorMiddleware
    {
        $cognitoConfig = $container->get('config')['cognito'] ?? [];

        if (empty($cognitoConfig['mock_url'])) {
            throw new RuntimeException(
                'Missing required Cognito config key "mock_url" — check COGNITO_MOCK_URL env var'
            );
        }

        return new AlbSimulatorMiddleware(
            new Client(),
            mockCognitoUrl: $cognitoConfig['mock_url'],
            devEmail:        $cognitoConfig['dev_email'] ?? 'dev-admin@local',
        );
    }
}
