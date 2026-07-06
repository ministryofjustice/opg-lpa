<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\Service\Cognito\Client as CognitoClient;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AlbSimulatorMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AlbSimulatorMiddleware
    {
        $cognitoConfig = $container->get('config')['cognito'] ?? [];

        if (empty($cognitoConfig['base_url'])) {
            throw new RuntimeException(
                'Missing required Cognito config key "base_url" — check COGNITO_BASE_URL env var'
            );
        }

        return new AlbSimulatorMiddleware(
            new CognitoClient(new Client(), $cognitoConfig['base_url']),
            $cognitoConfig['dev_email'] ?? 'dev-admin@local',
        );
    }
}
