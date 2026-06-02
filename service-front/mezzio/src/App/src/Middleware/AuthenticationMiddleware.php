<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\RequestAttribute;
use App\Authentication\AuthenticationService;
use App\Model\Service\Authentication\Identity\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Mezzio port of Application\Middleware\AuthenticationMiddleware.
 *
 * Checks that the user is authenticated before allowing access to protected
 * routes, redirecting to the login page with an appropriate reason if not.
 * Uses the Mezzio session directly instead of Laminas MVC session containers.
 *
 * Must run after IdentityTokenRefreshMiddleware (which populates the auth
 * service storage from the Mezzio session).
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    private const string REASON_TIMEOUT = 'timeout';
    private const string REASON_INTERNAL_SYSTEM_ERROR = 'internalSystemError';

    // Mezzio session key used by LoginHandler when storing the pre-auth URL.
    private const string SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();

        if ($identity instanceof User) {
            $tokenExpiresAt = $identity->tokenExpiresAt();
            if ($tokenExpiresAt !== null) {
                $request = $request->withAttribute(
                    'secondsUntilSessionExpires',
                    $tokenExpiresAt->getTimestamp() - time()
                );
            }

            $request = $request->withAttribute(RequestAttribute::IDENTITY, $identity);
        }

        $route = $request->getAttribute(RouteResult::class);
        if (!$route instanceof RouteResult) {
            return $handler->handle($request);
        }

        $matchedRoute   = $route->getMatchedRoute();
        $routeOptions   = $matchedRoute !== false ? ($matchedRoute->getOptions() ?: []) : [];
        $isUnauthenticated = isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true;

        if ($isUnauthenticated || $identity instanceof User) {
            return $handler->handle($request);
        }

        $matchedRouteName = $route->getMatchedRouteName();
        $routeName        = $matchedRouteName !== false ? $matchedRouteName : '';
        $allowRedirect    = !($routeName === 'user/delete' || str_starts_with($routeName, 'user/dashboard'));

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $reason  = $this->getUnauthorisedReason($session, $allowRedirect, $request->getUri()->getPath() ?: '');

        return new RedirectResponse(
            $this->urlHelper->generate('application.login', ['state' => $reason])
        );
    }

    private function getUnauthorisedReason(?SessionInterface $session, bool $allowRedirect, string $requestPath): string
    {
        if ($session instanceof SessionInterface) {
            if ($allowRedirect) {
                $session->set(self::SESSION_KEY_PRE_AUTH_URL, $requestPath);
            }

            $failureCode = $session->get(IdentityTokenRefreshMiddleware::SESSION_KEY_AUTH_FAILURE_CODE);
            if ($failureCode !== null) {
                return self::REASON_INTERNAL_SYSTEM_ERROR;
            }
        }

        return self::REASON_TIMEOUT;
    }
}
