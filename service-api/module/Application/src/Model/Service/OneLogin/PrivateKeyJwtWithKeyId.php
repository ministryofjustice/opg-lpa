<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\OpenIDClient\AuthMethod\AbstractJwtAuth;
use Facile\OpenIDClient\Client\ClientInterface;
use Override;

use function Facile\OpenIDClient\base64url_encode;
use function Facile\OpenIDClient\get_endpoint_uri;

/**
 * The `private_key_jwt` client assertion for the One Login /token request.
 *
 * https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/authenticate-your-user/#create-a-jwt
 */
final class PrivateKeyJwtWithKeyId extends AbstractJwtAuth
{
    /**
     * One Login recommends the assertion expires after 5 minutes.
     */
    public const int LIFETIME_SECONDS = 300;

    public function __construct(
        private readonly KeyPairManager $keyPairManager,
    ) {
    }

    #[Override]
    public function getSupportedMethod(): string
    {
        return 'private_key_jwt';
    }

    /**
     * The token request parameters in $claims are sent in the request body by the
     * parent class, so they are deliberately not added to the assertion.
     *
     * @param array<string, mixed> $claims
     */
    #[Override]
    protected function createAuthJwt(ClientInterface $client, array $claims = []): string
    {
        $clientId = $client->getMetadata()->getClientId();
        $now      = time();

        return JwtSigner::sign($this->keyPairManager->jwk(), [
            'iss' => $clientId,
            'sub' => $clientId,
            'aud' => get_endpoint_uri($client, 'token_endpoint'),
            'iat' => $now,
            'exp' => $now + self::LIFETIME_SECONDS,
            'jti' => base64url_encode(random_bytes(32)),
        ]);
    }
}
