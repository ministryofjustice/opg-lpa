<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Refreshes the authenticated user's API token on every request.
 *
 * Replicates the bootstrapIdentity() logic from Module::onBootstrap(), converted
 * to PSR-15 middleware. Skipped for health-check endpoints and the session-state
 * endpoint (which calls this path without a full session context).
 *
 * We don't redirect unauthenticated users here — AuthenticationMiddleware handles
 * that on protected routes. We only clear a stale or failed identity so that
 * downstream middleware sees an accurate authentication state.
 *
 * Must run after SessionBootstrapMiddleware.
 */
class IdentityTokenRefreshMiddleware implements MiddlewareInterface
{
    private const array EXCLUDED_PATHS = [
        '/ping/elb',
        '/ping/json',
    ];

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UserService $userService,
        private readonly SessionUtility $sessionUtility,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Skip health-check endpoints entirely.
        if (!in_array($path, self::EXCLUDED_PATHS, true)) {
            // The session-state endpoint is called without needing token refresh.
            $updateToken = ($path !== '/session-state');

            $this->refreshIdentity($updateToken);
        }

        return $handler->handle($request);
    }

    /**
     * Checks the current identity's token against the API and updates or clears
     * it as appropriate — mirroring Module::bootstrapIdentity().
     */
    private function refreshIdentity(bool $updateToken): void
    {
        /** @var Identity|null $identity */
        $identity = $this->authenticationService->getIdentity();

        if ($identity === null || !$updateToken) {
            return;
        }

        try {
            $info = $this->userService->getTokenInfo($identity->token());

            if ($info['success'] && $info['expiresIn'] !== null) {
                // Update the cached expiry time in the session identity.
                $identity->tokenExpiresIn($info['expiresIn']);
            } else {
                $this->authenticationService->clearIdentity();

                // Record the failure reason so AuthenticationMiddleware can surface it.
                if (($info['failureCode'] ?? 0) >= 500) {
                    $this->sessionUtility->setInMvc(
                        ContainerNamespace::AUTH_FAILURE_REASON,
                        'reason',
                        'Internal system error',
                    );
                    $this->sessionUtility->setInMvc(
                        ContainerNamespace::AUTH_FAILURE_REASON,
                        'code',
                        $info['failureCode'],
                    );
                }
            }
        } catch (ApiException) {
            $this->authenticationService->clearIdentity();
        }
    }
}
