<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\JwtSigner;
use Application\Model\Service\OneLogin\KeyPairManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PHPUnit\Framework\TestCase;

class JwtSignerTest extends TestCase
{
    public function testSignsTheClaimsWithRs256AndNamesTheKey(): void
    {
        $keyPairManager = new KeyPairManager(KeyPairManagerTest::rsaPrivateKey(), 'test-kid');

        $jwt = JwtSigner::sign($keyPairManager->jwk(), ['iss' => 'client-id', 'jti' => 'abc']);

        $jws = (new CompactSerializer())->unserialize($jwt);

        // One Login picks the public key from our JWKS by this kid
        $this->assertSame(['alg' => 'RS256', 'kid' => 'test-kid'], $jws->getSignature(0)->getProtectedHeader());
        $this->assertSame(['iss' => 'client-id', 'jti' => 'abc'], json_decode((string) $jws->getPayload(), true));
        $this->assertTrue(
            (new JWSVerifier(new AlgorithmManager([new RS256()])))
                ->verifyWithKey($jws, $keyPairManager->jwk()->toPublic(), 0),
            'The JWT did not verify against the public half of the signing key',
        );
    }
}
