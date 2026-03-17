<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Middleware\RequestAttribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
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
 * Implements both the laminas-mvc listener interface (via listen()) and the PSR-7
 * MiddlewareInterface (via process()), so it can run in a laminas-mvc PipeSpec during
 * the Mezzio migration as well as in a full Mezzio pipeline. In the middleware path it
 * also sets the authenticated identity and the number of seconds until the session
 * expires as request attributes for downstream middleware to consume.
 */
class AuthenticationListener extends AbstractListenerAggregate implements MiddlewareInterface
{
    private const string REASON_TIMEOUT = 'timeout';
    private const string REASON_INTERNAL_SYSTEM_ERROR = 'internalSystemError';

    public function __construct(
        private readonly SessionUtility $sessionUtility,
        private readonly AuthenticationService $authenticationService,
        private readonly ?UrlHelper $urlHelper = null,
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'listen'],
            $priority
        );
    }

    public function listen(MvcEvent $event): ?MVCResponse
    {
        $allowRedirect = true;
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch === null) {
            return null;
        }

        $identity = $this->authenticationService->getIdentity();

        // Identity is stored under ContainerNamespace::IDENTITY when a user successfully logs in
        if ($routeMatch->getParam('unauthenticated_route', false) || $identity instanceof User) {
            if ($identity instanceof User) {
                $event->setParam(EventParameter::IDENTITY, $identity);
            }

            return null;
        }

        $route = $routeMatch->getMatchedRouteName() ?: '';

        if ($route === 'user/delete' || str_starts_with($route, 'user/dashboard')) {
            $allowRedirect = false;
        }

        $this->sessionUtility->unsetInMvc(ContainerNamespace::USER_DETAILS, 'user');
        $request = $event->getRequest();
        $uriString = $request instanceof HttpRequest ? $request->getUriString() : '';
        $reason = $this->getUnauthorisedReason($allowRedirect, $uriString);

        $url = $event->getRouter()->assemble(['state' => $reason], ['name' => 'login']);

        $response = new Response();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);

        return $response;
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

        $routeOptions = $route->getMatchedRoute()->getOptions() ?: [];
        $isUnauthenticatedRoute = isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true;

        if ($isUnauthenticatedRoute === true || $identity instanceof User) {
            return $handler->handle($request);
        }

        $allowRedirect = true;

        $routeName = $route->getMatchedRouteName() ?: '';
        if ($routeName === 'user/delete' || str_starts_with($routeName, 'user/dashboard')) {
            $allowRedirect = false;
        }

        $this->sessionUtility->unsetInMvc(ContainerNamespace::USER_DETAILS, 'user');
        $requestPath = $request->getUri()->getPath() ?: '';
        $reason = $this->getUnauthorisedReason($allowRedirect, $requestPath);

        // TODO(mezzio): update routeName when we setup Mezzio routes
        return new RedirectResponse(
            $this->urlHelper->generate('login', [], ['state' => $reason])
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
