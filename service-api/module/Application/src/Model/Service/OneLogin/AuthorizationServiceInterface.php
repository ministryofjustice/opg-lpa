<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\TokenSetInterface;

interface AuthorizationServiceInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function getAuthorizationUri(ClientInterface $client, array $params = []): string;

    /**
     * @param array<string, mixed> $params
     */
    public function callback(
        ClientInterface $client,
        array $params,
        ?string $redirectUri = null,
        ?AuthSessionInterface $authSession = null,
        ?int $maxAge = null,
    ): TokenSetInterface;
}
