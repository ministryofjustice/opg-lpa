<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

final class JwtSigner
{
    /**
     * @param JWK                  $key    our signing key, from KeyPairManager, which always has a `kid`
     * @param array<string, mixed> $claims
     */
    public static function sign(JWK $key, array $claims): string
    {
        $jws = (new JWSBuilder(new AlgorithmManager([new RS256()])))
            ->create()
            ->withPayload(json_encode($claims, JSON_THROW_ON_ERROR))
            ->addSignature($key, ['alg' => 'RS256', 'kid' => $key->get('kid')])
            ->build();

        return (new CompactSerializer())->serialize($jws, 0);
    }
}
