<?php

namespace Application\Model\Service\OneLogin;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryTrait;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use DateTime;
use Facile\OpenIDClient\Session\AuthSession;
use MakeShared\Logging\LoggerTrait;
use MakeShared\OneLogin\LinkReason;
use RuntimeException;

class Service extends AbstractService
{
    use LoggerTrait;
    use LogRepositoryTrait;
    use UserRepositoryTrait;

    private ?AuthorisationClientManager $clientManager = null;
    private ?AuthorizationServiceInterface $authorizationService = null;
    private ?AuthenticationService $authenticationService = null;
    private ?SharedSpaceRepositoryInterface $sharedSpaceRepository = null;
    /** @var callable(positive-int): string */
    private $randomBytes;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct()
    {
        $this->randomBytes = random_bytes(...);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setAuthorisationClientManager(AuthorisationClientManager $manager): void
    {
        $this->clientManager = $manager;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setAuthorizationService(AuthorizationServiceInterface $authorizationService): void
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setAuthenticationService(AuthenticationService $authenticationService): void
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSharedSpaceRepository(SharedSpaceRepositoryInterface $sharedSpaceRepository): void
    {
        $this->sharedSpaceRepository = $sharedSpaceRepository;
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
     * @return array{state: string, nonce: string, url: string}
     * @throws RuntimeException
     */
    public function createAuthenticationRequest(string $redirectUrl): array
    {
        if ($this->clientManager === null) {
            throw new RuntimeException('AuthorisationClientManager must be set');
        }

        if ($this->authorizationService === null) {
            throw new RuntimeException('AuthorizationService must be set');
        }

        $generator = $this->randomBytes;

        $state = bin2hex($generator(12));
        $nonce = bin2hex($generator(16));

        $url = $this->authorizationService->getAuthorizationUri(
            $this->clientManager->get(),
            [
                'redirect_uri' => $redirectUrl,
                'scope'        => 'openid email',
                'state'        => $state,
                'nonce'        => $nonce,
                'vtr'          => '["Cl.Cm"]',
            ],
        );

        $this->getLogger()->info('auth.onelogin.request_created', [
            'redirect_host' => parse_url($redirectUrl, PHP_URL_HOST),
        ]);

        return ['state' => $state, 'nonce' => $nonce, 'url' => $url];
    }

    /**
     * Exchange the authorisation code and validate the ID token.
     *
     * @return array{
     *     linked: bool,
     *     sub: string,
     *     email: string,
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
        string $redirectUri,
    ): array {
        if ($this->clientManager === null) {
            throw new RuntimeException('AuthorisationClientManager must be set');
        }

        if ($this->authorizationService === null) {
            throw new RuntimeException('AuthorizationService must be set');
        }

        if ($this->authenticationService === null) {
            throw new RuntimeException('AuthenticationService must be set');
        }

        if ($this->sharedSpaceRepository === null) {
            throw new RuntimeException('SharedSpaceRepository must be set');
        }

        $authSession = AuthSession::fromArray([
            'state'   => $state,
            'nonce'   => $nonce,
            'customs' => ['redirect_uri' => $redirectUri],
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

        if ($tokenSet->getIdToken() === null) {
            throw new OneLoginAuthenticationException('missing_id_token');
        }

        $claims = $tokenSet->claims();

        $sub = $claims['sub'] ?? null;

        if (!is_string($sub) || $sub === '') {
            throw new OneLoginAuthenticationException('missing_sub_claim');
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

        if (!is_string($email) || $email === '') {
            throw new OneLoginAuthenticationException('missing_email_claim');
        }

        $user = $this->getUserRepository()->getByOneLoginSub($sub);

        if (!$user instanceof UserInterface) {
            return [
                'linked'   => false,
                'sub'      => $sub,
                'email'    => $email,
                'identity' => null,
            ];
        }

        $userId = $user->id();

        if ($userId === null) {
            throw new OneLoginAuthenticationException('missing_user_id');
        }

        $this->getUserRepository()->updateLastLoginTime($userId);

        $tokenDetails = $this->authenticationService->issueAuthToken($user);

        $this->getLogger()->info('auth.onelogin.callback_success', [
            'user_id' => $userId,
        ]);

        return [
            'linked'   => true,
            'sub'      => $sub,
            'email'    => $email,
            'identity' => [
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
    ): array {
        if ($this->authenticationService === null) {
            throw new RuntimeException('AuthenticationService must be set');
        }

        if ($this->sharedSpaceRepository === null) {
            throw new RuntimeException('SharedSpaceRepository must be set');
        }

        $user = $this->getUserRepository()->getByUsername($username);

        if (!$user instanceof UserInterface) {
            $deletionLog = $this->getLogRepository()->getLogByIdentityHash($this->hashIdentity($username));

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

        $this->getUserRepository()->setOneLoginSub($authResult['userId'], $oneLoginSub, $oneLoginEmail);

        $this->getLogger()->info('auth.onelogin.link_success', [
            'user_id' => $authResult['userId'],
        ]);

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
     * @return array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: null}
     */
    public function createAndLinkAccount(string $sub, string $oneLoginEmail): array
    {
        if ($this->authenticationService === null) {
            throw new RuntimeException('AuthenticationService must be set');
        }

        $generator = $this->randomBytes;
        $now       = new MillisecondDateTime();
        $identity  = $this->placeholderIdentity($sub);

        do {
            $userId = bin2hex($generator(16));

            $created = $this->getUserRepository()->create($userId, [
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

        $this->getUserRepository()->updateLastLoginTime($userId);

        $user = $this->getUserRepository()->getById($userId);

        if (!$user instanceof UserInterface) {
            throw new RuntimeException('Failed to load newly created One Login account');
        }

        $tokenDetails = $this->authenticationService->issueAuthToken($user);

        $this->getLogger()->info('auth.onelogin.create_success', [
            'user_id' => $userId,
        ]);

        return [
            'userId'         => $userId,
            'token'          => $tokenDetails['token'],
            'tokenExpiresAt' => $tokenDetails['expiresAt']->format('c'),
            'lastLogin'      => (new DateTime())->format('c'),
            'sharedSpaceId'  => null,
        ];
    }

    /**
     * @return array{linked: false, reason: string}
     */
    private function rejectLink(string $reason): array
    {
        $this->getLogger()->info('auth.onelogin.link_rejected', [
            'reason' => $reason,
        ]);

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
