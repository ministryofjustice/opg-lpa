<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use RuntimeException;

class OneLoginService
{
    public function __construct(
        private readonly ApiClient $client,
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

        return ['state' => $result['state'], 'nonce' => $result['nonce'], 'url' => $result['url']];
    }

    /**
     * Exchanges the authorisation code for an LPA identity or a pending-link payload.
     *
     * @return array{linked: false, sub: string, email: string}|array{linked: true, sub: string, email: string, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}
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
        ) {
            throw new RuntimeException(
                'Invalid response from API: linked, sub and email are required'
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

        /** @var array{linked: false, sub: string, email: string}|array{linked: true, sub: string, email: string, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}} $result */
        return $result;
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
    ): array {
        /** @var array<string, mixed>|null $result */
        $result = $this->client->httpPost(
            '/v2/auth/onelogin/link',
            [
                'username'    => $email,
                'password'    => $password,
                'oneLoginSub' => $oneLoginSub,
            ],
            anonymous: true,
        );

        if (!is_array($result) || !array_key_exists('linked', $result) || !is_bool($result['linked'])) {
            throw new RuntimeException('Invalid response from API: linked is required');
        }

        if ($result['linked'] === false) {
            $reason = $result['reason'] ?? null;

            return ['linked' => false, 'reason' => is_string($reason) && $reason !== '' ? $reason : 'unknown'];
        }

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

        /** @var array{linked: true, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}} $result */
        return $result;
    }
}
