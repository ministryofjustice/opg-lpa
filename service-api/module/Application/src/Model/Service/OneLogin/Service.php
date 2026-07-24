<?php

namespace Application\Model\Service\OneLogin;

use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use DateTime;
use Facile\OpenIDClient\Session\AuthSession;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

class Service extends AbstractService
{
    use LoggerTrait;
    use UserRepositoryTrait;

    private array $config = [];
    private ?AuthorisationClientManager $clientManager = null;
    private ?AuthorizationServiceInterface $authorizationService = null;
    private ?AuthenticationService $authenticationService = null;
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
    public function setConfig(array $config): void
    {
        $this->config = $config;
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
     * @return array{linked: bool, sub: string, email: ?string, identity: ?array}
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

        $authSession = AuthSession::fromArray([
            'state'   => $state,
            'nonce'   => $nonce,
            'customs' => ['redirect_uri' => $redirectUri],
        ]);

        try {
            $tokenSet = $this->authorizationService->callback(
                $this->clientManager->get(),
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

        $email = isset($claims['email']) && is_string($claims['email']) ? $claims['email'] : null;

        $user = $this->getUserRepository()->getByOneLoginSub($sub);

        if (!$user instanceof UserInterface) {
            return [
                'linked'   => false,
                'sub'      => $sub,
                'email'    => $email,
                'identity' => null,
            ];
        }

        $this->getUserRepository()->updateLastLoginTime($user->id());

        if ($user->failedLoginAttempts() > 0) {
            $this->getUserRepository()->resetFailedLoginCounter($user->id());
        }

        $tokenDetails = $this->authenticationService->issueAuthToken($user);

        $this->getLogger()->info('auth.onelogin.callback_success', [
            'user_id' => $user->id(),
        ]);

        return [
            'linked'   => true,
            'sub'      => $sub,
            'email'    => $email,
            'identity' => [
                'userId'         => $user->id(),
                'token'          => $tokenDetails['token'],
                'tokenExpiresAt' => $tokenDetails['expiresAt']->format('c'),
                'lastLogin'      => ($user->lastLoginAt() ?? new DateTime())->format('c'),
            ],
        ];
    }
}
