<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use Laminas\Diactoros\Uri;
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
     * @return array{state: string, nonce: string, url: string}
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
            || empty($result['url'])
            || !is_string($result['state'])
            || !is_string($result['nonce'])
            || !is_string($result['url'])
        ) {
            throw new RuntimeException(
                'Invalid response from API: state, nonce and url must be non-empty strings'
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

        return ['state' => $result['state'], 'nonce' => $result['nonce'], 'url' => $url];
    }

    /**
     * Exchanges the authorisation code for an LPA identity or a pending-link payload.
     *
     * @return array{linked: false, sub: string, email: string, idToken: string}|array{linked: true, sub: string, email: string, idToken: string, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}
     * @throws RuntimeException
     */
    public function callback(
        string $code,
        string $state,
        string $nonce,
        string $redirectUri,
    ): array {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/callback',
            [
                'code'         => $code,
                'state'        => $state,
                'nonce'        => $nonce,
                'redirect_uri' => $redirectUri,
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
        ) {
            throw new RuntimeException(
                'Invalid response from API: linked, sub, email and idToken are required'
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

        /** @var array{linked: false, sub: string, email: string, idToken: string}|array{linked: true, sub: string, email: string, idToken: string, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}} $result */
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
    ): array {
        /** @var array{linked: true, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}|array{linked: false, reason: string} $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/link',
            [
                'username'      => $email,
                'password'      => $password,
                'oneLoginSub'   => $oneLoginSub,
                'oneLoginEmail' => $oneLoginEmail,
            ],
            anonymous: true,
        );

        return $result;
    }

    /**
     * @return bool true if the token was valid and any matching session was ended
     * @throws RuntimeException
     */
    public function backChannelLogout(#[\SensitiveParameter] string $logoutToken): bool
    {
        /** @var array{accepted: bool, reason?: string} $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/backchannel-logout',
            [
                'logoutToken' => $logoutToken,
            ],
            anonymous: true,
        );

        return ($result['accepted'] ?? false) === true;
    }

    /**
     * @return array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}
     * @throws RuntimeException
     */
    public function createAndLinkAccount(
        string $oneLoginSub,
        string $oneLoginEmail,
    ): array {
        /** @var array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string} $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/create',
            [
                'oneLoginSub'   => $oneLoginSub,
                'oneLoginEmail' => $oneLoginEmail,
            ],
            anonymous: true,
        );

        return $result;
    }
}
