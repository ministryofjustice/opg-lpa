<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\ApiClient\Client as ApiClient;
use RuntimeException;

final class OneLoginService
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

        return $result;
    }
}
