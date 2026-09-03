<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\KeyPairManager;
use Facile\JoseVerifier\JWK\MemoryJwksProvider;
use Facile\OpenIDClient\AuthMethod\PrivateKeyJwt;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\IssuerMetadata;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Laminas\Diactoros\Request;
use PHPUnit\Framework\TestCase;

class ClientAssertionTest extends TestCase
{
    private const CLIENT_ID     = 'test-client-id';
    private const TOKEN_ENDPOINT = 'https://oidc.integration.account.gov.uk/token';

    public function testRsaKeyProducesAVerifiableRs256Assertion(): void
    {
        $privateKeyPem = KeyPairManagerTest::rsaPrivateKey();

        $jwk = (new KeyPairManager($privateKeyPem, 'test-kid-rsa'))->jwk();

        $assertion = $this->createAssertion($jwk);

        $header = $this->protectedHeaderOf($assertion);

        $this->assertSame('RS256', $header['alg']);

        $this->assertTrue(
            $this->verify($assertion, $jwk->toPublic(), new RS256()),
            'The client assertion did not verify against the public half of the signing key',
        );
    }

    public function testAssertionCarriesTheClaimsOneLoginExpects(): void
    {
        $jwk = (new KeyPairManager(KeyPairManagerTest::rsaPrivateKey(), 'test-kid-rsa'))->jwk();

        $claims = $this->payloadOf($this->createAssertion($jwk));

        $this->assertSame(self::CLIENT_ID, $claims['iss']);
        $this->assertSame(self::CLIENT_ID, $claims['sub']);
        $this->assertSame(self::TOKEN_ENDPOINT, $claims['aud']);
        $this->assertNotEmpty($claims['jti']);
        $this->assertGreaterThan(time(), $claims['exp']);
        $this->assertLessThanOrEqual(time(), $claims['iat']);
    }

    private function createAssertion(JWK $jwk): string
    {
        $request = (new PrivateKeyJwt())->createRequest(
            new Request(self::TOKEN_ENDPOINT, 'POST'),
            $this->clientFor($jwk),
            [],
        );

        parse_str((string) $request->getBody(), $body);

        $this->assertSame(
            'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            $body['client_assertion_type'],
        );

        return $body['client_assertion'];
    }

    private function clientFor(JWK $jwk): ClientInterface
    {
        $issuer = $this->createMock(IssuerInterface::class);
        $issuer->method('getMetadata')->willReturn(IssuerMetadata::fromArray([
            'issuer'                 => 'https://oidc.integration.account.gov.uk/',
            'authorization_endpoint' => 'https://oidc.integration.account.gov.uk/authorize',
            'jwks_uri'               => 'https://oidc.integration.account.gov.uk/.well-known/jwks.json',
            'token_endpoint'         => self::TOKEN_ENDPOINT,
        ]));

        $jwksProvider = new MemoryJwksProvider(
            ['keys' => [$jwk->jsonSerialize()]],
        );

        $client = $this->createMock(ClientInterface::class);
        $client->method('getIssuer')->willReturn($issuer);
        $client->method('getJwksProvider')->willReturn($jwksProvider);
        $client->method('getMetadata')->willReturn(ClientMetadata::fromArray([
            'client_id'                  => self::CLIENT_ID,
            'token_endpoint_auth_method' => 'private_key_jwt',
        ]));

        return $client;
    }

    /** @return array<string, mixed> */
    private function protectedHeaderOf(string $jws): array
    {
        return (new CompactSerializer())->unserialize($jws)->getSignature(0)->getProtectedHeader();
    }

    /** @return array<string, mixed> */
    private function payloadOf(string $jws): array
    {
        $payload = (new CompactSerializer())->unserialize($jws)->getPayload();

        return json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
    }

    private function verify(string $jws, JWK $publicKey, object $algorithm): bool
    {
        $verifier = new JWSVerifier(new AlgorithmManager([$algorithm]));

        return $verifier->verifyWithKey(
            (new CompactSerializer())->unserialize($jws),
            $publicKey,
            0,
        );
    }
}
