<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\JoseVerifier\Builder\IdTokenVerifierBuilder;
use Psr\SimpleCache\CacheInterface;
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
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Returns the `sub` and `jti` of a valid logout token.
     *
     * @return array{sub: string, jti: string}
     * @throws LogoutTokenException if the token fails any validation step
     */
    public function verify(#[\SensitiveParameter] string $logoutToken): array
    {
        $client = $this->clientManager->get();

        $verifier = IdTokenVerifierBuilder::create(
            $client->getIssuer()->getMetadata()->toArray(),
            [
                'client_id' => $client->getMetadata()->getClientId(),
                'id_token_signed_response_alg' => self::EXPECTED_ALG,
            ],
        )
            ->withJwksProvider(new ThrottledJwksProvider($client->getIssuer()->getJwksProvider(), $this->cache))
            ->withClockTolerance(self::CLOCK_TOLERANCE_SECONDS)
            ->build();

        try {
            $claims = $verifier->verify($logoutToken);
        } catch (Throwable $e) {
            throw new LogoutTokenException('invalid_token', 0, $e);
        }

        $this->assertLogoutEvent($claims['events'] ?? null);

        if (array_key_exists('nonce', $claims)) {
            throw new LogoutTokenException('nonce_present');
        }

        $sub = $claims['sub'] ?? null;
        $jti = $claims['jti'] ?? null;

        if (!is_string($sub) || $sub === '') {
            throw new LogoutTokenException('missing_sub');
        }

        if (!is_string($jti) || $jti === '') {
            throw new LogoutTokenException('missing_jti');
        }

        return ['sub' => $sub, 'jti' => $jti];
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
