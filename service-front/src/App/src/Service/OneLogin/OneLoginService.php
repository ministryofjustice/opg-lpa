<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use Laminas\Diactoros\Uri;
use MakeShared\OneLogin\UserType;
use RuntimeException;

class OneLoginService
{
    /**
     * @param array<string, string> $mockAuthorizationUrls Callback URI to mock authorization endpoint.
     */
    public function __construct(
        private readonly ApiClient $client,
        private readonly array $mockAuthorizationUrls = [],
    ) {
    }

    /**
     * @return array{state: string, nonce: string, codeVerifier: string, url: string}
     * @throws RuntimeException
     */
    public function start(string $redirectUri): array
    {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpGet(
            '/v2/auth/onelogin/start',
            ['redirect_url' => $redirectUri],
            anonymous: true,
        );

        if (
            !is_array($result)
            || empty($result['state'])
            || empty($result['nonce'])
            || empty($result['codeVerifier'])
            || empty($result['url'])
            || !is_string($result['state'])
            || !is_string($result['nonce'])
            || !is_string($result['codeVerifier'])
            || !is_string($result['url'])
        ) {
            throw new RuntimeException(
                'Invalid response from API: state, nonce, codeVerifier and url must be non-empty strings'
            );
        }

        $url = $result['url'];
        if ($this->mockAuthorizationUrls !== []) {
            if (!isset($this->mockAuthorizationUrls[$redirectUri])) {
                throw new RuntimeException('No mock One Login authorization endpoint configured for callback URI');
            }

            $authorizationUri = new Uri($url);
            $mockUri = new Uri($this->mockAuthorizationUrls[$redirectUri]);
            $url = (string) $mockUri->withQuery($authorizationUri->getQuery());
        }

        return [
            'state'        => $result['state'],
            'nonce'        => $result['nonce'],
            'codeVerifier' => $result['codeVerifier'],
            'url'          => $url,
        ];
    }

    /**
     * Exchanges the authorisation code for an LPA identity or a pending-link payload.
     *
     * @return array{linked: false, sub: string, email: string, idToken: string, signedInAt: int}|array{linked: true, sub: string, email: string, idToken: string, signedInAt: int, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}
     * @throws RuntimeException
     */
    public function callback(
        string $code,
        string $state,
        string $nonce,
        #[\SensitiveParameter] string $codeVerifier,
        string $redirectUri,
    ): array {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/callback',
            [
                'code'          => $code,
                'state'         => $state,
                'nonce'         => $nonce,
                'code_verifier' => $codeVerifier,
                'redirect_uri'  => $redirectUri,
            ],
            anonymous: true,
        );

        if (
            !is_array($result)
            || !array_key_exists('linked', $result)
            || !is_bool($result['linked'])
            || empty($result['sub'])
            || !is_string($result['sub'])
            || empty($result['email'])
            || !is_string($result['email'])
            || empty($result['idToken'])
            || !is_string($result['idToken'])
            || !is_int($result['signedInAt'] ?? null)
        ) {
            throw new RuntimeException(
                'Invalid response from API: linked, sub, email, idToken and signedInAt are required'
            );
        }

        if ($result['linked']) {
            if (
                !isset($result['identity'])
                || !is_array($result['identity'])
                || empty($result['identity']['userId'])
                || empty($result['identity']['token'])
                || empty($result['identity']['tokenExpiresAt'])
                || empty($result['identity']['lastLogin'])
            ) {
                throw new RuntimeException(
                    'Invalid response from API: identity fields missing for linked account'
                );
            }
        }

        /** @var array{linked: false, sub: string, email: string, idToken: string, signedInAt: int}|array{linked: true, sub: string, email: string, idToken: string, signedInAt: int, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}} $result */
        return $result;
    }

    /**
     * Returns the One Login URL that ends the user's One Login session and then sends them to
     * $postLogoutRedirectUri.
     *
     * $mockRedirectUri is the same callback URI used to start the sign-in (e.g. "https://front-ssl/auth/redirect"),
     * used to look up the mock One Login host reachable from whichever front-end host is signing out, since the
     * end_session_endpoint returned by One Login's discovery document is not necessarily reachable from there.
     *
     * @throws RuntimeException
     */
    public function logoutUrl(
        #[\SensitiveParameter] string $idToken,
        string $postLogoutRedirectUri,
        ?string $mockRedirectUri = null,
    ): string {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/logout',
            [
                'idToken'               => $idToken,
                'postLogoutRedirectUri' => $postLogoutRedirectUri,
            ],
            anonymous: true,
        );

        if (!is_array($result) || empty($result['url']) || !is_string($result['url'])) {
            throw new RuntimeException('Invalid response from API: url must be a non-empty string');
        }

        $url = $result['url'];

        if ($this->mockAuthorizationUrls !== []) {
            if ($mockRedirectUri === null || !isset($this->mockAuthorizationUrls[$mockRedirectUri])) {
                throw new RuntimeException('No mock One Login authorization endpoint configured for callback URI');
            }

            $mockUri = new Uri($this->mockAuthorizationUrls[$mockRedirectUri]);
            $logoutUri = new Uri($url);
            $url = (string) $logoutUri
                ->withScheme($mockUri->getScheme())
                ->withHost($mockUri->getHost())
                ->withPort($mockUri->getPort());
        }

        return $url;
    }

    /**
     * Attempt to link an existing Make account to the One Login identity.
     *
     * @return array{linked: true, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}|array{linked: false, reason: string}
     * @throws RuntimeException
     */
    public function linkExistingAccount(
        #[\SensitiveParameter] string $email,
        #[\SensitiveParameter] string $password,
        string $oneLoginSub,
        string $oneLoginEmail,
        UserType $userType,
    ): array {
        /** @var array{linked: true, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}|array{linked: false, reason: string} $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/link',
            [
                'username'      => $email,
                'password'      => $password,
                'oneLoginSub'   => $oneLoginSub,
                'oneLoginEmail' => $oneLoginEmail,
                'userType'      => $userType->value,
            ],
            anonymous: true,
        );

        return $result;
    }

    /**
     * @return array{sub: string, signedOutAt: int}|null
     * @throws RuntimeException
     */
    public function backChannelLogout(#[\SensitiveParameter] string $logoutToken): ?array
    {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/backchannel-logout',
            [
                'logoutToken' => $logoutToken,
            ],
            anonymous: true,
        );

        if (!is_array($result)) {
            throw new RuntimeException('Invalid response from API: expected a logout result');
        }

        if (($result['accepted'] ?? false) !== true) {
            return null;
        }

        $sub         = $result['sub'] ?? null;
        $signedOutAt = $result['signedOutAt'] ?? null;

        if (!is_string($sub) || $sub === '' || !is_int($signedOutAt)) {
            throw new RuntimeException('Invalid response from API: sub and signedOutAt are required for an accepted logout');
        }

        return ['sub' => $sub, 'signedOutAt' => $signedOutAt];
    }

    /**
     * @return array{keys: list<array<string, mixed>>}
     * @throws RuntimeException
     */
    public function jwks(): array
    {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpGet('/v2/auth/onelogin/jwks', anonymous: true);

        if (!is_array($result) || !is_array($result['keys'] ?? null) || $result['keys'] === []) {
            throw new RuntimeException('Invalid response from API: keys must be a non-empty list');
        }

        /** @var array{keys: list<array<string, mixed>>} $result */
        return ['keys' => $result['keys']];
    }

    /**
     * @param array<string, mixed> $profile the user's name, date of birth and address
     * @return array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}
     * @throws RuntimeException
     */
    public function createAndLinkAccount(
        string $oneLoginSub,
        string $oneLoginEmail,
        UserType $userType,
        array $profile,
    ): array {
        /** @var array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string} $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/create',
            [
                'oneLoginSub'   => $oneLoginSub,
                'oneLoginEmail' => $oneLoginEmail,
                'userType'      => $userType->value,
                'profile'       => $profile,
            ],
            anonymous: true,
        );

        return $result;
    }
}
