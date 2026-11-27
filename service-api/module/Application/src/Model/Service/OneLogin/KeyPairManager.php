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

    private readonly JWK $signingKey;

    /** @var list<JWK> */
    private readonly array $publishedKeys;

    /**
     * Each key is a PEM, or a base64-encoded PEM.
     *
     * @param string      $current  the key we sign with
     * @param string|null $next     published, ready to become the current key
     * @param string|null $previous published until One Login can no longer have it cached
     */
    public function __construct(
        #[\SensitiveParameter] string $current,
        #[\SensitiveParameter] ?string $next = null,
        #[\SensitiveParameter] ?string $previous = null,
    ) {
        $this->signingKey = $this->load($current);

        $publishedKeys = [$this->signingKey];

        foreach ([$next, $previous] as $key) {
            if ($key !== null) {
                $publishedKeys[] = $this->load($key);
            }
        }

        $keyIds = array_map(static fn (JWK $key): mixed => $key->get('kid'), $publishedKeys);

        if (count(array_unique($keyIds)) !== count($keyIds)) {
            throw new RuntimeException('OneLogin signing keys must all be different');
        }

        $this->publishedKeys = $publishedKeys;
    }

    public function jwk(): JWK
    {
        return $this->signingKey;
    }

    /**
     * @return array{keys: list<array<string, mixed>>}
     */
    public function publicJwks(): array
    {
        return [
            'keys' => array_map(static fn (JWK $key): array => $key->toPublic()->all(), $this->publishedKeys),
        ];
    }

    private function load(#[\SensitiveParameter] string $key): JWK
    {
        if ($key === '') {
            throw new RuntimeException('OneLogin private key must not be empty');
        }

        try {
            $jwk = JWKFactory::createFromKey($this->resolvePem($key), null, ['use' => 'sig']);
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

        return new JWK($jwk->all() + ['alg' => self::ALGORITHM, 'kid' => $jwk->thumbprint('sha256')]);
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
