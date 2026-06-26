<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Storage\MezzioSessionStorage;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use App\Authentication\AuthenticationService;
use App\Model\Service\Authentication\Identity\User as Identity;
use App\Service\UserDetails as UserService;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Reads the authenticated identity from the Mezzio session, populates the
 * LPA auth service storage (so AbstractService::getUserId() works), then
 * refreshes the API token.
 *
 * Must run after Mezzio\Session\SessionMiddleware.
 */
class IdentityTokenRefreshMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public const string SESSION_KEY_IDENTITY = 'identity';
    public const string SESSION_KEY_AUTH_FAILURE_CODE = 'auth_failure_code';

    private const array EXCLUDED_PATHS = [
        '/ping/elb',
        '/ping/json',
    ];

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly MezzioSessionStorage $storage,
        private readonly ApiClient $apiClient,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!in_array($path, self::EXCLUDED_PATHS, true)) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

            if ($session instanceof SessionInterface) {
                $this->storage->setSession($session);
                $identity = $this->storage->read();
                if ($identity !== null) {
                    $this->apiClient->updateToken($identity->token());
                }

                $updateToken = ($path !== '/session-state');
                $this->refreshIdentity($session, $updateToken);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Refreshes the token against the API and updates or clears the identity,
     * writing any failure code to the Mezzio session.
     */
    private function refreshIdentity(SessionInterface $session, bool $updateToken): void
    {
        /** @var Identity|null $identity */
        $identity = $this->authenticationService->getIdentity();

        if ($identity === null || !$updateToken) {
            return;
        }

        try {
            $info = $this->userService->getTokenInfo($identity->token());

            if ($info['success'] && $info['expiresIn'] !== null) {
                $identity->tokenExpiresIn($info['expiresIn']);
                $this->storage->write($identity);
            } else {
                $this->getLogger()->warning('Token refresh failed, clearing identity', [
                    'success'     => $info['success'],
                    'failureCode' => $info['failureCode'] ?? null,
                    'expiresIn'   => $info['expiresIn'] ?? null,
                ]);

                $this->authenticationService->clearIdentity();

                if (($info['failureCode'] ?? 0) >= 500) {
                    $session->set(self::SESSION_KEY_AUTH_FAILURE_CODE, $info['failureCode']);
                }
            }
        } catch (ApiException $e) {
            $this->getLogger()->warning('ApiException during token refresh, clearing identity', [
                'message' => $e->getMessage(),
                'code'    => $e->getStatusCode(),
            ]);
            $this->authenticationService->clearIdentity();
        }
    }
}
