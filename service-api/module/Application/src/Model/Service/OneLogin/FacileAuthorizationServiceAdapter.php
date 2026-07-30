<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\UserInfoService;
use Facile\OpenIDClient\Session\AuthSessionInterface;
use Facile\OpenIDClient\Token\TokenSetInterface;

final class FacileAuthorizationServiceAdapter implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly UserInfoService $userInfoService,
    ) {
    }

    public function getAuthorizationUri(ClientInterface $client, array $params = []): string
    {
        return $this->authorizationService->getAuthorizationUri($client, $params);
    }

    public function callback(
        ClientInterface $client,
        array $params,
        ?string $redirectUri = null,
        ?AuthSessionInterface $authSession = null,
        ?int $maxAge = null,
    ): TokenSetInterface {
        return $this->authorizationService->callback($client, $params, $redirectUri, $authSession, $maxAge);
    }

    public function getUserInfo(ClientInterface $client, TokenSetInterface $tokenSet): array
    {
        return $this->userInfoService->getUserInfo($client, $tokenSet);
    }
}
