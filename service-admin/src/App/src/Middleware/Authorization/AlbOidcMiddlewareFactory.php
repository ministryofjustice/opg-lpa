<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\Service\Alb\PublicKeyClient;
use GuzzleHttp\Client;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AlbOidcMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AlbOidcMiddleware
    {
        $config = $container->get('config');
        $cognitoConfig = $config['cognito'] ?? [];
        $albConfig = $config['alb'] ?? [];

        if (empty($cognitoConfig['client_id'])) {
            throw new RuntimeException(
                'Missing required Cognito config key "client_id" — check OPG_COGNITO_CLIENT_ID env var'
            );
        }

        foreach (['public_key_base_url', 'admin_arn'] as $key) {
            if (empty($albConfig[$key])) {
                throw new RuntimeException(
                    sprintf('Missing required ALB config key "%s" — check OPG_ALB_%s env var', $key, strtoupper($key))
                );
            }
        }

        // Cache each ALB public key for 24 hours — keys rotate infrequently. APCu is
        // shared across all PHP-FPM workers on the same host, so only one outbound call
        // is made per TTL.
        $publicKeyClient = new PublicKeyClient(
            new Client(['timeout' => 5, 'connect_timeout' => 3]),
            $albConfig['public_key_base_url'],
        );

        return new AlbOidcMiddleware(
            $publicKeyClient,
            $albConfig['admin_arn'],
            $cognitoConfig['client_id'],
            $container->get(UrlHelper::class),
        );
    }
}
