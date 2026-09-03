<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use RuntimeException;

class KeyPairManager
{
    /**
     * The algorithm we sign the `private_key_jwt` client assertion with.
     * The local mock does not verify the assertion at all,
     * so anything else would pass locally.
     */
    private const KEY_TYPE = 'RSA';

    private const ALGORITHM = 'RS256';

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
            $jwk = JWKFactory::createFromKey(
                $this->resolvePem($this->privateKey),
                null,
                ['use' => 'sig', 'kid' => $this->keyId],
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Failed to load OneLogin private key: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $keyType = $jwk->has('kty') ? $jwk->get('kty') : null;

        if ($keyType !== self::KEY_TYPE) {
            throw new RuntimeException(sprintf(
                'OneLogin private key must be %s, got "%s"',
                self::KEY_TYPE,
                is_string($keyType) ? $keyType : 'unknown',
            ));
        }

        return new JWK($jwk->all() + ['alg' => self::ALGORITHM]);
    }

    private function resolvePem(string $key): string
    {
        if (str_contains($key, 'BEGIN')) {
            return $key;
        }

        $decoded = base64_decode($key, true);

        if ($decoded === false) {
            throw new RuntimeException('OneLogin private key is neither a PEM nor base64-encoded PEM');
        }

        if (!str_contains($decoded, 'BEGIN')) {
            throw new RuntimeException('OneLogin private key is neither a PEM nor base64-encoded PEM');
        }

        return $decoded;
    }
}
