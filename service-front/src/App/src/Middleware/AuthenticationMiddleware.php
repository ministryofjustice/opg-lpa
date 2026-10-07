<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\RequestAttribute;
use App\Authentication\AuthenticationService;
use App\Handler\Traits\RequestInspectorTrait;
use App\Service\SafeRedirectPath;
use App\Model\Service\Authentication\Identity\User;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\OneLoginSignOut;
use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks that the user is authenticated before allowing access to protected
 * routes, redirecting to the login page with an appropriate reason if not.
 *
 * Must run after IdentityTokenRefreshMiddleware (which populates the auth
 * service storage from the Mezzio session).
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    use RequestInspectorTrait;

    private const string REASON_TIMEOUT = 'timeout';
    private const string REASON_INTERNAL_SYSTEM_ERROR = 'internal-system-error';

    // Mezzio session key used by LoginHandler when storing the pre-auth URL.
    public const string SESSION_KEY_PRE_AUTH_URL = 'pre_auth_request_url';

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly UrlHelper $urlHelper,
        private readonly LoggerInterface $logger,
        private readonly OneLoginSessionManager $oneLoginSessionManager,
        private readonly OneLoginSignOut $oneLoginSignOut,
        private readonly RedirectUriBuilder $redirectUriBuilder,
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

        $session  = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $reason   = $this->getUnauthorisedReason($session, $allowRedirect, $request->getUri()->getPath() ?: '');
        $loginUrl = $this->urlHelper->generate('application.login', ['state' => $reason]);

        if ($reason === self::REASON_TIMEOUT && $session instanceof SessionInterface) {
            $oneLoginUrl = $this->oneLoginLogoutUrl($request, $session, $loginUrl);

            if ($oneLoginUrl !== null) {
                return new RedirectResponse($oneLoginUrl);
            }
        }

        return new RedirectResponse($loginUrl);
    }

    private function oneLoginLogoutUrl(ServerRequestInterface $request, SessionInterface $session, string $loginUrl): ?string
    {
        $idToken = $this->oneLoginSessionManager->getIdToken($session);

        // Only a page load can take the user to One Login; a background (XHR) request keeps the
        // normal redirect and leaves the ID token for the next page load.
        if ($idToken === null || $this->isXmlHttpRequest($request)) {
            return null;
        }

        $url = $this->oneLoginSignOut->url(
            $idToken,
            ($this->redirectUriBuilder)($request->getUri(), $loginUrl),
            ($this->redirectUriBuilder)($request->getUri()),
        );

        // Forget the token only once we're sending the user to One Login, so a failure can be
        // retried by a later sign-out and the return trip to the timeout page cannot loop.
        if ($url !== null) {
            $this->oneLoginSessionManager->forgetIdToken($session);
        }

        return $url;
    }

    private function getUnauthorisedReason(?SessionInterface $session, bool $allowRedirect, string $requestPath): string
    {
        if ($session instanceof SessionInterface) {
            if ($allowRedirect) {
                $safePath = SafeRedirectPath::filter($requestPath);

                if ($safePath !== null) {
                    $session->set(self::SESSION_KEY_PRE_AUTH_URL, $safePath);
                } else {
                    $session->unset(self::SESSION_KEY_PRE_AUTH_URL);

                    if ($requestPath !== '') {
                        $this->logger->warning('auth.pre_auth_url_rejected', [
                            'length' => strlen($requestPath),
                        ]);
                    }
                }
            }

            $failureCode = $session->get(IdentityTokenRefreshMiddleware::SESSION_KEY_AUTH_FAILURE_CODE);
            if ($failureCode !== null) {
                return self::REASON_INTERNAL_SYSTEM_ERROR;
            }
        }

        return self::REASON_TIMEOUT;
    }
}
