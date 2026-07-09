<?php

namespace App\Service\ApiClient;

use Http\Adapter\Guzzle7\Client as GuzzleClient;
use MakeShared\Service\SecretService;
use Psr\Container\ContainerInterface;

/**
 * Class ClientFactory
 * @package App\Service\ApiClient
 */
class ClientFactory
{
    /**
     * @param ContainerInterface $container
     * @return Client
     */
    public function __invoke(ContainerInterface $container)
    {
        $httpClient = GuzzleClient::createWithConfig([
            'verify' => false,
        ]);

        $config = $container->get('config');

        $secret = SecretService::resolve(
            arn: $config['admin_service_secret_arn'] ?? null,
            endpoint: $config['secrets_manager_endpoint'] ?? null,
        );

        return new Client(
            $httpClient,
            $config['api_base_uri'],
            $secret,
        );
    }
}
