<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use RuntimeException;

final class KeyPairManager
{
    public const ALGORITHM = 'ES256';

    public function __construct(
        #[\SensitiveParameter] private readonly string $privateKey,
        private readonly string $keyId,
    ) {
        if ($this->privateKey === '') {
            throw new RuntimeException('OneLogin private key must not be empty');
        }

        if ($this->keyId === '') {
            throw new RuntimeException('OneLogin key ID must not be empty');
        }
    }

    public function jwk(): JWK
    {
        try {
            return JWKFactory::createFromKey(
                $this->privateKey,
                null,
                ['alg' => self::ALGORITHM, 'use' => 'sig', 'kid' => $this->keyId],
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Failed to load OneLogin private key: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
