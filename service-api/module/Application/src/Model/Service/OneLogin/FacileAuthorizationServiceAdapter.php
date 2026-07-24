<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\TokenSetInterface;

final class FacileAuthorizationServiceAdapter implements AuthorizationServiceInterface
{
    public function __construct(private readonly AuthorizationService $inner)
    {
    }

    public function getAuthorizationUri(ClientInterface $client, array $params = []): string
    {
        return $this->inner->getAuthorizationUri($client, $params);
    }

    public function callback(
        ClientInterface $client,
        array $params,
        ?string $redirectUri = null,
        ?AuthSessionInterface $authSession = null,
        ?int $maxAge = null,
    ): TokenSetInterface {
        return $this->inner->callback($client, $params, $redirectUri, $authSession, $maxAge);
    }
}
