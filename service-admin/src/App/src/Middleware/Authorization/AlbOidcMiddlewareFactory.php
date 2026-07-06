<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

class AlbOidcMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $cognitoConfig = $container->get('config')['cognito'];

        $httpClient = new Client();

        return new AlbOidcMiddleware(
            $httpClient,
            $cognitoConfig['base_url'],
            $cognitoConfig['issuer'],
            $cognitoConfig['client_id'],
        );
    }
}
