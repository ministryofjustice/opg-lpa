<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use MakeShared\Telemetry\Tracer;

class ApiClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        $config = $container->get('config');
        $apiUri = $config['api_client']['api_uri'] ?? '';

        $tracer = $container->get(Tracer::class);

        return new Client(
            new GuzzleClient(),
            (string) $apiUri,
            [],
            $tracer,
            $container->get(LoggerInterface::class),
        );
    }
}
