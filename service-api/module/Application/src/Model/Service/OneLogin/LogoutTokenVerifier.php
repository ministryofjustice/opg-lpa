<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\JoseVerifier\Builder\IdTokenVerifierBuilder;
use Facile\JoseVerifier\Exception\InvalidTokenExceptionInterface;
use Facile\JoseVerifier\Exception\RuntimeException as JoseRuntimeException;
use RuntimeException;
use Throwable;

/**
 * Validates a GOV.UK One Login back-channel logout token.
 *
 * Implements the validation steps in
 * https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/managing-your-users-sessions/
 */
class LogoutTokenVerifier
{
    public const string BACKCHANNEL_LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    public const int CLOCK_TOLERANCE_SECONDS = 60;

    private const string EXPECTED_ALG = 'ES256';

    public function __construct(
        private readonly AuthorisationClientManager $clientManager,
    ) {
    }

    /**
     * Returns the `sub`, `jti` and `iat` of a valid logout token.
     *
     * @return array{sub: string, jti: string, iat: int}
     * @throws LogoutTokenException if the token fails any validation step
     * @throws RuntimeException if One Login's signing keys cannot be fetched
     */
    public function verify(#[\SensitiveParameter] string $logoutToken): array
    {
        $client = $this->clientManager->get();

        $clientId = $client->getMetadata()->getClientId();

        if ($clientId === '') {
            throw new RuntimeException('OneLogin client id is not configured');
        }

        $verifier = IdTokenVerifierBuilder::create(
            $client->getIssuer()->getMetadata()->toArray(),
            [
                'client_id' => $clientId,
                'id_token_signed_response_alg' => self::EXPECTED_ALG,
            ],
        )
            ->withJwksProvider($client->getIssuer()->getJwksProvider())
            ->withClockTolerance(self::CLOCK_TOLERANCE_SECONDS)
            ->build();

        try {
            $claims = $verifier->verify($logoutToken);
        } catch (InvalidTokenExceptionInterface $e) {
            throw new LogoutTokenException('invalid_token', 0, $e);
        } catch (JoseRuntimeException $e) {
            // Only the JWKS providers throw this: our failure, not a bad token, so One Login can retry.
            throw new RuntimeException('Unable to fetch One Login signing keys', 0, $e);
        } catch (Throwable $e) {
            throw new LogoutTokenException('invalid_token', 0, $e);
        }

        $this->assertLogoutEvent($claims['events'] ?? null);

        if (array_key_exists('nonce', $claims)) {
            throw new LogoutTokenException('nonce_present');
        }

        $sub = $claims['sub'] ?? null;
        $jti = $claims['jti'] ?? null;
        $iat = $claims['iat'] ?? null;

        if (!is_string($sub) || $sub === '') {
            throw new LogoutTokenException('missing_sub');
        }

        if (!is_string($jti) || $jti === '') {
            throw new LogoutTokenException('missing_jti');
        }

        if (!is_int($iat)) {
            throw new LogoutTokenException('missing_iat');
        }

        return ['sub' => $sub, 'jti' => $jti, 'iat' => $iat];
    }

    private function assertLogoutEvent(mixed $events): void
    {
        if (!is_array($events) || !array_key_exists(self::BACKCHANNEL_LOGOUT_EVENT, $events)) {
            throw new LogoutTokenException('invalid_events');
        }

        if (!is_array($events[self::BACKCHANNEL_LOGOUT_EVENT])) {
            throw new LogoutTokenException('invalid_events');
        }
    }
}
