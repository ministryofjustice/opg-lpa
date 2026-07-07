<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\Service\Cognito\Client as CognitoClient;
use GuzzleHttp\Client;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AlbOidcMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AlbOidcMiddleware
    {
        $cognitoConfig = $container->get('config')['cognito'] ?? [];

        foreach (['base_url', 'issuer', 'client_id'] as $key) {
            if (empty($cognitoConfig[$key])) {
                throw new RuntimeException(
                    sprintf('Missing required Cognito config key "%s" — check COGNITO_%s env var', $key, strtoupper($key))
                );
            }
        }

        // Cache JWKS for 1 hour — keys rotate infrequently. APCu is shared across
        // all PHP-FPM workers on the same host, so only one outbound call is made per TTL.
        return new AlbOidcMiddleware(
            new CognitoClient(new Client(), $cognitoConfig['base_url']),
            $cognitoConfig['issuer'],
            $cognitoConfig['client_id'],
            $container->get(UrlHelper::class),
        );
    }
}
