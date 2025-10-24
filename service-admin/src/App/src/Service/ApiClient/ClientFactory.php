<?php

namespace App\Service\ApiClient;

use Http\Adapter\Guzzle7\Client as GuzzleClient;
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

        //  Get the end point targets from the config
        $config = $container->get('config');

        return new Client($httpClient, $config['api_base_uri']);
    }
}
