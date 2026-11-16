<?php

namespace Application\Model\Service\OneLogin;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use DateTime;
use Facile\OpenIDClient\Session\AuthSession;
use InvalidArgumentException;
use MakeShared\DataModel\User\User as ProfileUserModel;
use MakeShared\Logging\MetricsLogger;
use MakeShared\OneLogin\LinkReason;
use MakeShared\OneLogin\UserType;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

use function Facile\OpenIDClient\base64url_encode;

class Service
{
    /**
     * Must not accept the same jti twice within three minutes.
     */
    private const int LOGOUT_TOKEN_REPLAY_WINDOW_SECONDS = 180;

    /**
     * The credential trust level we request: medium authentication, no identity proving.
     * One Login returns it in the ID token's `vot` claim, which must match.
     */
    private const string CREDENTIAL_TRUST_LEVEL = 'Cl.Cm';

    private const array VECTOR_OF_TRUST = [self::CREDENTIAL_TRUST_LEVEL];

    /**
     * One Login recommends the request object expires after 5 minutes.
     */
    private const int REQUEST_OBJECT_LIFETIME_SECONDS = 300;

    /** @var callable(positive-int): string */
    private $randomBytes;

    public function __construct(
        private readonly MetricsLogger $metrics,
        private readonly LoggerInterface $logger,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LogRepositoryInterface $logRepository,
        private readonly SharedSpaceRepositoryInterface $sharedSpaceRepository,
        private readonly AuthenticationService $authenticationService,
        private readonly AuthorisationClientManager $clientManager,
        private readonly AuthorizationServiceInterface $authorizationService,
        private readonly LogoutTokenVerifier $logoutTokenVerifier,
        private readonly CacheInterface $cache,
        private readonly KeyPairManager $keyPairManager,
    ) {
        $this->randomBytes = random_bytes(...);
    }

    /**
     * Optional seam for tests: override the random-byte generator.
     *
     * @param callable(positive-int): string $generator
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setRandomByteGenerator(callable $generator): void
    {
        $this->randomBytes = $generator;
    }

    /**
     * Build and return an OIDC authorisation request.
     *
     * @return array{state: string, nonce: string, codeVerifier: string, url: string}
     * @throws RuntimeException
     */
    public function createAuthenticationRequest(string $redirectUrl): array
    {
        $generator = $this->randomBytes;

        $state        = bin2hex($generator(12));
        $nonce        = bin2hex($generator(16));
        $codeVerifier = base64url_encode($generator(32));

        $client   = $this->clientManager->get();
        $clientId = $client->getMetadata()->getClientId();
        $scope    = 'openid email';

        // The claims One Login requires in a request object:
        // https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/authenticate-your-user/#secure-your-authorisation-request-parameters-with-jwt
        $requestObject = JwtSigner::sign($this->keyPairManager->jwk(), [
            'aud'                   => $client->getIssuer()->getMetadata()->getAuthorizationEndpoint(),
            'iss'                   => $clientId,
            'client_id'             => $clientId,
            'exp'                   => time() + self::REQUEST_OBJECT_LIFETIME_SECONDS,
            'response_type'         => 'code',
            'scope'                 => $scope,
            'redirect_uri'          => $redirectUrl,
            'state'                 => $state,
            'nonce'                 => $nonce,
            'vtr'                   => self::VECTOR_OF_TRUST,
            'code_challenge'        => base64url_encode(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
        ]);

        $url = $this->authorizationService->getAuthorizationUri($client, [
            'scope'   => $scope,
            'request' => $requestObject,
        ]);

        $this->logger->info('auth.onelogin.request_created', [
            'redirect_host' => parse_url($redirectUrl, PHP_URL_HOST),
        ]);

        return ['state' => $state, 'nonce' => $nonce, 'codeVerifier' => $codeVerifier, 'url' => $url];
    }

    /**
     * Our public keys, for the JWKS endpoint One Login reads them from.
     *
     * @return array{keys: list<array<string, mixed>>}
     * @throws RuntimeException
     */
    public function publicJwks(): array
    {
        return $this->keyPairManager->publicJwks();
    }

    /**
     * Build the URL that signs the user out of GOV.UK One Login.
     *
     * One Login only honours post_logout_redirect_uri when id_token_hint is present, and the
     * URI must match one registered against our client.
     *
     * @throws RuntimeException
     */
    public function createLogoutRequest(#[\SensitiveParameter] string $idToken, string $postLogoutRedirectUri): string
    {
        /** @var mixed $endSessionEndpoint */
        $endSessionEndpoint = $this->clientManager->get()->getIssuer()->getMetadata()->get('end_session_endpoint');

        if (!is_string($endSessionEndpoint) || $endSessionEndpoint === '') {
            throw new RuntimeException('One Login discovery document has no end_session_endpoint');
        }

        return $endSessionEndpoint . '?' . http_build_query(
            [
                'id_token_hint'            => $idToken,
                'post_logout_redirect_uri' => $postLogoutRedirectUri,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
    }

    /**
     * Exchange the authorisation code and validate the ID token.
     *
     * `signedInAt` is the ID token's `iat`: when One Login signed the user in. The front
     * compares it with any later back-channel sign-out before letting a pending link go on.
     *
     * @return array{
     *     linked: bool,
     *     sub: string,
     *     email: string,
     *     idToken: string,
     *     signedInAt: int,
     *     identity: null|array{
     *         userId: string,
     *         token: string,
     *         tokenExpiresAt: string,
     *         lastLogin: string,
     *         sharedSpaceId: ?string
     *     }
     * }
     * @throws OneLoginAuthenticationException
     */
    public function handleCallback(
        #[\SensitiveParameter] string $code,
        #[\SensitiveParameter] string $state,
        #[\SensitiveParameter] string $nonce,
        #[\SensitiveParameter] string $codeVerifier,
        string $redirectUri,
    ): array {
        $authSession = AuthSession::fromArray([
            'state'         => $state,
            'nonce'         => $nonce,
            'code_verifier' => $codeVerifier,
            'customs'       => ['redirect_uri' => $redirectUri],
        ]);

        $client = $this->clientManager->get();

        try {
            $tokenSet = $this->authorizationService->callback(
                $client,
                ['code' => $code, 'state' => $state],
                $redirectUri,
                $authSession,
            );
        } catch (\Throwable $e) {
            throw new OneLoginAuthenticationException(
                'token_exchange_failed',
                'One Login token exchange failed',
                0,
                $e,
            );
        }

        $idToken = $tokenSet->getIdToken();

        if ($idToken === null) {
            throw new OneLoginAuthenticationException('missing_id_token');
        }

        $claims = $tokenSet->claims();

        $sub = $claims['sub'] ?? null;

        if (!is_string($sub) || $sub === '') {
            throw new OneLoginAuthenticationException('missing_sub_claim');
        }

        // One Login: "The vot claim must contain the credential trust level you asked for
        // in the request to the /authorize endpoint."
        $vot = $claims['vot'] ?? null;

        if ($vot === null) {
            throw new OneLoginAuthenticationException('missing_vot_claim');
        }

        if ($vot !== self::CREDENTIAL_TRUST_LEVEL) {
            $this->logger->warning('auth.onelogin.unexpected_vot', ['vot' => $vot]);

            throw new OneLoginAuthenticationException('invalid_vot_claim');
        }

        $signedInAt = $claims['iat'] ?? null;

        if (!is_int($signedInAt)) {
            throw new OneLoginAuthenticationException('missing_iat_claim');
        }

        // GOV.UK One Login returns the email from the UserInfo endpoint, not in the
        // ID token, so fetch it from there. (getUserInfo also checks the userinfo
        // `sub` matches the ID token's.)
        try {
            $userInfo = $this->authorizationService->getUserInfo($client, $tokenSet);
        } catch (\Throwable $e) {
            throw new OneLoginAuthenticationException('userinfo_fetch_failed', '', 0, $e);
        }

        $email = $userInfo['email'] ?? null;

        $email = is_string($email) ? $this->normaliseEmail($email) : '';

        if ($email === '') {
            throw new OneLoginAuthenticationException('missing_email_claim');
        }

        $user = $this->userRepository->getByOneLoginSub($sub);

        if (!$user instanceof UserInterface) {
            return [
                'linked'     => false,
                'sub'        => $sub,
                'email'      => $email,
                'idToken'    => $idToken,
                'signedInAt' => $signedInAt,
                'identity'   => null,
            ];
        }

        $userId = $user->id();

        if ($userId === null) {
            throw new OneLoginAuthenticationException('missing_user_id');
        }

        $this->userRepository->updateLastLoginTime($userId);

        $this->refreshOneLoginEmail($userId, $user->oneLoginEmail(), $email);

        $tokenDetails = $this->authenticationService->issueAuthToken($user);

        $this->metrics->userLoggedIn($userId);

        return [
            'linked'     => true,
            'sub'        => $sub,
            'email'      => $email,
            'idToken'    => $idToken,
            'signedInAt' => $signedInAt,
            'identity'   => [
                'userId'         => $userId,
                'token'          => $tokenDetails['token'],
                'tokenExpiresAt' => $tokenDetails['expiresAt']->format('c'),
                'lastLogin'      => ($user->lastLoginAt() ?? new \DateTime())->format('c'),
                'sharedSpaceId'  => $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId),
            ],
        ];
    }

    /**
     * Link an existing Make account (identified by its current login email +
     * password) to a GOV.UK One Login identity that isn't yet associated with any
     * Make account.
     *
     * On success the One Login sub and email are stored on the account and the local
     * password is cleared; the login email (`identity`) is left untouched.
     *
     * @return array{linked: true, identity: array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string}}|array{linked: false, reason: string}
     */
    public function linkExistingAccount(
        #[\SensitiveParameter] string $username,
        #[\SensitiveParameter] string $password,
        string $oneLoginSub,
        string $oneLoginEmail,
        UserType $userType,
    ): array {
        $user = $this->userRepository->getByUsername($username);

        if (!$user instanceof UserInterface) {
            $deletionLog = $this->logRepository->getLogByIdentityHash($this->hashIdentity($username));

            return $this->rejectLink(is_array($deletionLog) ? LinkReason::ACCOUNT_DELETED : LinkReason::ACCOUNT_NOT_FOUND);
        }

        $existingSub = $user->oneLoginSub();

        if (is_string($existingSub) && $existingSub !== '' && $existingSub !== $oneLoginSub) {
            return $this->rejectLink(LinkReason::ALREADY_LINKED);
        }

        $authResult = $this->authenticationService->withPassword($username, $password, true);

        if (is_string($authResult)) {
            return $this->rejectLink($this->mapAuthFailure($authResult));
        }

        $this->userRepository->setOneLoginSub($authResult['userId'], $oneLoginSub, $oneLoginEmail, $userType === UserType::Professional);

        $this->metrics->userLinkedExistingMakeAccount($authResult['userId']);

        $this->logUserIdentified($userType, $authResult['userId']);

        $lastLogin = $authResult['last_login'] ?? new DateTime();

        return [
            'linked'   => true,
            'identity' => [
                'userId'         => $authResult['userId'],
                'token'          => $authResult['token'],
                'tokenExpiresAt' => $authResult['expiresAt']->format('c'),
                'lastLogin'      => $lastLogin->format('c'),
                'sharedSpaceId'  => $authResult['sharedSpaceId'],
            ],
        ];
    }

    /**
     * Creates a Make account for a One Login identity, with the user type and details
     * given during onboarding.
     *
     * @param array<string, mixed> $profile the user's name, date of birth and address
     * @return array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: null}
     * @throws InvalidArgumentException if the profile is not valid
     */
    public function createAndLinkAccount(string $sub, string $oneLoginEmail, UserType $userType, array $profile): array
    {
        $generator = $this->randomBytes;
        $now       = new MillisecondDateTime();
        $identity  = $this->placeholderIdentity($sub);

        do {
            $userId = bin2hex($generator(16));

            $created = $this->userRepository->create($userId, [
                'is_professional'       => $userType === UserType::Professional,
                'profile'               => $this->validProfile($userId, $now, $profile),
                'identity'              => $identity,
                'password_hash'         => null,
                'activation_token'      => null,
                'active'                => true,
                'activated'             => $now,
                'created'               => $now,
                'last_updated'          => $now,
                'failed_login_attempts' => 0,
                'one_login_sub'         => $sub,
                'one_login_email'       => $oneLoginEmail,
            ]);
        } while (!$created);

        $this->userRepository->updateLastLoginTime($userId);

        $user = $this->userRepository->getById($userId);

        if (!$user instanceof UserInterface) {
            throw new RuntimeException('Failed to load newly created One Login account');
        }

        $tokenDetails = $this->authenticationService->issueAuthToken($user);

        $this->metrics->userCreated($userId);

        $this->logUserIdentified($userType, $userId);

        return [
            'userId'         => $userId,
            'token'          => $tokenDetails['token'],
            'tokenExpiresAt' => $tokenDetails['expiresAt']->format('c'),
            'lastLogin'      => (new DateTime())->format('c'),
            'sharedSpaceId'  => null,
        ];
    }

    /**
     * Builds the new account's profile from the details the client may set, and validates it.
     *
     * @param array<string, mixed> $profile
     * @throws InvalidArgumentException
     */
    private function validProfile(string $userId, DateTime $now, array $profile): ProfileUserModel
    {
        $details = array_intersect_key($profile, array_flip(['name', 'dob', 'address']));

        try {
            $user = new ProfileUserModel(['id' => $userId, 'createdAt' => $now, 'updatedAt' => $now] + $details);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('profile is not valid', 0, $e);
        }

        if ($user->validate()->hasErrors()) {
            throw new InvalidArgumentException('profile is not valid');
        }

        return $user;
    }

    /**
     * Stores the email One Login has just given us if it differs from the one we hold.
     */
    private function refreshOneLoginEmail(
        string $userId,
        #[\SensitiveParameter] ?string $storedEmail,
        #[\SensitiveParameter] string $incomingEmail,
    ): void {
        if ($storedEmail !== null && $this->normaliseEmail($storedEmail) === $this->normaliseEmail($incomingEmail)) {
            return;
        }

        try {
            $this->userRepository->setOneLoginEmail($userId, $incomingEmail);
        } catch (\Throwable $e) {
            $this->logger->warning('auth.onelogin.email_refresh_failed', [
                'user_id' => $userId,
                'exception' => $e,
            ]);

            return;
        }

        $this->logger->info('auth.onelogin.email_refreshed', [
            'user_id' => $userId,
        ]);
    }

    private function normaliseEmail(#[\SensitiveParameter] string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * The verified `sub` and the token's `iat` (when the user signed out) are returned so
     * the front can stop a pending account link for a user who has no Make account yet.
     *
     * @return array{accepted: true, sub: string, signedOutAt: int}|array{accepted: false, reason: string}
     */
    public function handleBackChannelLogout(#[\SensitiveParameter] string $logoutToken): array
    {
        try {
            ['sub' => $sub, 'jti' => $jti, 'iat' => $signedOutAt] = $this->logoutTokenVerifier->verify($logoutToken);
        } catch (LogoutTokenException $e) {
            return $this->rejectLogout($e->getMessage());
        }

        if ($this->hasSeenLogoutToken($jti)) {
            return $this->rejectLogout('replayed_jti');
        }

        $user = $this->userRepository->getByOneLoginSub($sub);
        $userId = $user instanceof UserInterface ? $user->id() : null;

        if ($userId !== null) {
            $this->userRepository->clearAuthToken($userId);
        }

        $this->rememberLogoutToken($jti);

        if ($userId === null) {
            $this->metrics->userFailedToLogout('no_user');
        } else {
            $this->metrics->userSessionEndedByOneLogin($userId);
        }

        return ['accepted' => true, 'sub' => $sub, 'signedOutAt' => $signedOutAt];
    }

    /**
     * @return array{accepted: false, reason: string}
     */
    private function rejectLogout(string $reason): array
    {
        $this->metrics->userFailedToLogout($reason);

        return ['accepted' => false, 'reason' => $reason];
    }

    private function hasSeenLogoutToken(string $jti): bool
    {
        return $this->cache->has($this->logoutTokenCacheKey($jti));
    }

    private function rememberLogoutToken(string $jti): void
    {
        $this->cache->set(
            $this->logoutTokenCacheKey($jti),
            true,
            self::LOGOUT_TOKEN_REPLAY_WINDOW_SECONDS,
        );
    }

    private function logoutTokenCacheKey(string $jti): string
    {
        return 'logout_jti_' . hash('sha256', $jti);
    }

    private function logUserIdentified(UserType $userType, string $userId): void
    {
        match ($userType) {
            UserType::Lay => $this->metrics->userIdentifiedAsLay($userId),
            UserType::Professional => $this->metrics->userIdentifiedAsProfessional($userId),
        };
    }

    /**
     * @return array{linked: false, reason: string}
     */
    private function rejectLink(string $reason): array
    {
        $this->metrics->userFailedToLinkExistingMakeAccount($reason);

        return ['linked' => false, 'reason' => $reason];
    }

    private function mapAuthFailure(string $result): string
    {
        return match ($result) {
            'account-not-active' => LinkReason::ACCOUNT_NOT_ACTIVE,
            'account-locked/max-login-attempts',
            'invalid-user-credentials/account-locked' => LinkReason::ACCOUNT_LOCKED,
            default => LinkReason::INVALID_CREDENTIALS,
        };
    }

    private function hashIdentity(#[\SensitiveParameter] string $identity): string
    {
        return hash('sha512', strtolower(trim($identity)));
    }

    /**
     * A non-null, guaranteed-unique placeholder for the `identity` (login email)
     * column of a created One Login account. The `onelogin:` prefix makes it obvious
     * the value is not a real email; uniqueness follows from the sub being unique.
     */
    private function placeholderIdentity(string $sub): string
    {
        return 'onelogin:' . $sub;
    }
}
