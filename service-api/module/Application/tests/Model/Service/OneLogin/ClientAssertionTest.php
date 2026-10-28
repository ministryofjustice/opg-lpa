<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\KeyPairManager;
use Application\Model\Service\OneLogin\PrivateKeyJwtWithKeyId;
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

    private KeyPairManager $keyPairManager;

    protected function setUp(): void
    {
        $this->keyPairManager = new KeyPairManager(KeyPairManagerTest::rsaPrivateKey(), 'test-kid-rsa');
    }

    public function testRsaKeyProducesAVerifiableRs256Assertion(): void
    {
        $assertion = $this->createAssertion();

        $header = $this->protectedHeaderOf($assertion);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('test-kid-rsa', $header['kid']);

        $this->assertTrue(
            $this->verify($assertion, $this->keyPairManager->jwk()->toPublic(), new RS256()),
            'The client assertion did not verify against the public half of the signing key',
        );
    }

    public function testAssertionCarriesTheClaimsOneLoginExpects(): void
    {
        $claims = $this->payloadOf($this->createAssertion());

        $this->assertSame(['iss', 'sub', 'aud', 'iat', 'exp', 'jti'], array_keys($claims));
        $this->assertSame(self::CLIENT_ID, $claims['iss']);
        $this->assertSame(self::CLIENT_ID, $claims['sub']);
        $this->assertSame(self::TOKEN_ENDPOINT, $claims['aud']);
        $this->assertNotEmpty($claims['jti']);
        $this->assertSame(PrivateKeyJwtWithKeyId::LIFETIME_SECONDS, $claims['exp'] - $claims['iat']);
        $this->assertLessThanOrEqual(time(), $claims['iat']);
    }

    public function testTokenRequestParametersGoInTheBodyNotTheAssertion(): void
    {
        $body = $this->tokenRequestBody([
            'grant_type'    => 'authorization_code',
            'code'          => 'the-code',
            'code_verifier' => 'the-verifier',
        ]);

        $this->assertSame('the-code', $body['code']);
        $this->assertSame('the-verifier', $body['code_verifier']);

        $claims = $this->payloadOf($body['client_assertion']);

        $this->assertArrayNotHasKey('code', $claims);
        $this->assertArrayNotHasKey('code_verifier', $claims);
    }

    public function testEachAssertionHasAUniqueJti(): void
    {
        $this->assertNotSame(
            $this->payloadOf($this->createAssertion())['jti'],
            $this->payloadOf($this->createAssertion())['jti'],
        );
    }

    private function createAssertion(): string
    {
        return $this->tokenRequestBody([])['client_assertion'];
    }

    /**
     * @param array<string, string> $parameters
     * @return array<string, string>
     */
    private function tokenRequestBody(array $parameters): array
    {
        $request = (new PrivateKeyJwtWithKeyId($this->keyPairManager))->createRequest(
            new Request(self::TOKEN_ENDPOINT, 'POST'),
            $this->client(),
            $parameters,
        );

        parse_str((string) $request->getBody(), $body);

        $this->assertSame(
            'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            $body['client_assertion_type'],
        );

        return $body;
    }

    private function client(): ClientInterface
    {
        $issuer = $this->createMock(IssuerInterface::class);
        $issuer->method('getMetadata')->willReturn(IssuerMetadata::fromArray([
            'issuer'                 => 'https://oidc.integration.account.gov.uk/',
            'authorization_endpoint' => 'https://oidc.integration.account.gov.uk/authorize',
            'jwks_uri'               => 'https://oidc.integration.account.gov.uk/.well-known/jwks.json',
            'token_endpoint'         => self::TOKEN_ENDPOINT,
        ]));

        $client = $this->createMock(ClientInterface::class);
        $client->method('getIssuer')->willReturn($issuer);
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
