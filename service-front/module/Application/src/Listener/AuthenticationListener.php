<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $route = '';
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch) {
            if ($routeMatch->getParam('unauthenticated_route', false) || $this->authenticationService->getIdentity() instanceof User) {
                return null;
            }

            $route = $routeMatch->getMatchedRouteName();

            if ($route === 'user/delete' || str_starts_with($route, 'user/dashboard')) {
                $allowRedirect = false;
            }
        } else {
            return null;
        }

        $reason = $this->getUnauthorisedReason($allowRedirect, $route);

        $router = $event->getRouter();
        $url = $router->assemble(['state' => $reason], ['name' => 'login']);

        $response = new Response();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);

        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);

        // What do we want to do here?
        if (!$route) {
            return $handler->handle($request);
        }

        $isUnauthenticatedRoute = $route->getMatchedRoute()->getOptions()['unauthenticated_route'] ?? false;

        if ($isUnauthenticatedRoute === true || $this->authenticationService->getIdentity() instanceof User) {
            return $handler->handle($request);
        }

        $allowRedirect = true;

        if ($route->getMatchedRouteName() === 'user/delete' || str_starts_with($route->getMatchedRouteName(), 'user/dashboard')) {
            $allowRedirect = false;
        }

        $reason = $this->getUnauthorisedReason($allowRedirect, $route->getMatchedRoute()->getPath());

        // TODO update routeName when we setup Mezzio routes
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
