<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Storage\MezzioSessionStorage;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\User\Details as UserService;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Mezzio port of Application\Middleware\IdentityTokenRefreshMiddleware.
 *
 * Reads the authenticated identity from the Mezzio session, populates the
 * LPA auth service storage (so AbstractService::getUserId() works), then
 * refreshes the API token. Failure codes are written back to the Mezzio
 * session instead of the Laminas MVC session containers.
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
                // Wire the session into the shared storage so read()/write()/clear()
                // operate on the real Mezzio session for this request.
                $this->storage->setSession($session);

                // Propagate the current user's token onto the shared ApiClient so all
                // API calls on this request include the correct Token header.
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
                // Persist the refreshed expiry back via storage (writes to Mezzio session).
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
