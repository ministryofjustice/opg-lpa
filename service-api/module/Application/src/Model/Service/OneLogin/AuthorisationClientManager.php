<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class AuthorisationClientManager
{
    public const CACHE_TTL = 3600;

    public function __construct(
        private readonly string $clientId,
        private readonly string $discoveryUrl,
        private readonly KeyPairManager $keyPairManager,
        private readonly ClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    public function get(): \Facile\OpenIDClient\Client\ClientInterface
    {
        $metadataBuilder = (new MetadataProviderBuilder())
            ->setHttpClient($this->httpClient)
            ->setCache($this->cache)
            ->setCacheTtl(self::CACHE_TTL);

        $providerJwks = (new JwksProviderBuilder())
            ->withHttpClient($this->httpClient)
            ->withCache($this->cache)
            ->withCacheTtl(self::CACHE_TTL);

        $issuer = (new IssuerBuilder())
            ->setMetadataProviderBuilder($metadataBuilder)
            ->setJwksProviderBuilder($providerJwks)
            ->build($this->discoveryUrl);

        $clientMetadata = ClientMetadata::fromArray([
            'client_id'                  => $this->clientId,
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]);

        $ourJwks = (new JwksProviderBuilder())
            ->withJwks(['keys' => [$this->keyPairManager->jwk()->jsonSerialize()]]);

        return (new ClientBuilder())
            ->setHttpClient($this->httpClient)
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->setJwksProvider($ourJwks->build())
            ->build();
    }
}
