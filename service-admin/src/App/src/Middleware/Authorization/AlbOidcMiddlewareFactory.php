<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AlbOidcMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AlbOidcMiddleware
    {
        $cognitoConfig = $container->get('config')['cognito'] ?? [];

        foreach (['jwks_uri', 'issuer', 'client_id'] as $key) {
            if (empty($cognitoConfig[$key])) {
                throw new RuntimeException(
                    sprintf('Missing required Cognito config key "%s" — check COGNITO_%s env var', $key, strtoupper($key))
                );
            }
        }

        return new AlbOidcMiddleware(
            new Client(),
            $cognitoConfig['jwks_uri'],
            $cognitoConfig['issuer'],
            $cognitoConfig['client_id'],
        );
    }
}
