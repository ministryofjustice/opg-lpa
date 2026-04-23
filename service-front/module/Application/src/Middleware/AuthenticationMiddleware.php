<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks that the user is authenticated before allowing access to protected routes,
 * redirecting to the login page with an appropriate reason if not.
 *
 * Sets the authenticated identity and the number of seconds until the session
 * expires as request attributes for downstream middleware to consume.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    private const string REASON_TIMEOUT = 'timeout';
    private const string REASON_INTERNAL_SYSTEM_ERROR = 'internalSystemError';

    public function __construct(
        private readonly SessionUtility $sessionUtility,
        private readonly AuthenticationService $authenticationService,
        private readonly ?UrlHelper $urlHelper = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $this->authenticationService->getIdentity();

        // Set session expiry attribute for authenticated users (before any routing checks)
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

        $matchedRoute = $route->getMatchedRoute();
        $routeOptions = $matchedRoute !== false ? ($matchedRoute->getOptions() ?: []) : [];
        $isUnauthenticatedRoute = isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true;

        if ($isUnauthenticatedRoute === true || $identity instanceof User) {
            return $handler->handle($request);
        }

        $allowRedirect = true;

        $matchedRouteName = $route->getMatchedRouteName();
        $routeName = $matchedRouteName !== false ? $matchedRouteName : '';
        if ($routeName === 'user/delete' || str_starts_with($routeName, 'user/dashboard')) {
            $allowRedirect = false;
        }

        $this->sessionUtility->unsetInMvc(ContainerNamespace::USER_DETAILS, 'user');
        $requestPath = $request->getUri()->getPath() ?: '';
        $reason = $this->getUnauthorisedReason($allowRedirect, $requestPath);

        // TODO(mezzio): update routeName when we setup Mezzio routes
        if ($this->urlHelper === null) {
            return new RedirectResponse('/login/' . $reason);
        }

        return new RedirectResponse(
            $this->urlHelper->generate('login', ['state' => $reason])
        );
    }

    protected function getUnauthorisedReason(bool $allowRedirect, string $requestUri): string
    {
        if ($allowRedirect) {
            $this->sessionUtility->setInMvc(
                ContainerNamespace::PRE_AUTH_REQUEST,
                'url',
                $requestUri
            );
        }

        // If the user's identity was cleared because of a genuine timeout,
        // redirect to the login page with session timeout; otherwise,
        // redirect to the login page and show the "service unavailable" message.
        $authFailureCode = $this->sessionUtility->getFromMvc(ContainerNamespace::AUTH_FAILURE_REASON, 'code');

        if (is_null($authFailureCode)) {
            return self::REASON_TIMEOUT;
        }

        return self::REASON_INTERNAL_SYSTEM_ERROR;
    }
}
