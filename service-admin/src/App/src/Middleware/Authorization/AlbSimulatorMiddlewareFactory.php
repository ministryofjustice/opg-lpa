<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

class AlbSimulatorMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $httpClient = new Client();

        return new AlbSimulatorMiddleware(
            $httpClient,
            mockCognitoUrl: $config['cognito']['mock_url'],  // e.g. http://mock-cognito:8080
            devEmail:        $config['cognito']['dev_email'] ?? 'dev-admin@local',
        );
    }
}
