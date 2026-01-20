<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Fetches user details and makes them available in the event/request/session for downstream
 * middleware and controllers to use.
 */
class UserDetailsListener extends AbstractListenerAggregate implements MiddlewareInterface
{
    public function __construct(
        protected SessionUtility $sessionUtility,
        protected Details $userService,
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'listen'],
            $priority
        );
    }

    public function listen(MvcEvent $event): ?MVCResponse
    {
        $routeMatch = $event->getRouteMatch();

        if ($routeMatch === null || $routeMatch->getParam('unauthenticated_route', false)) {
            return null;
        }

        $userDetails = $this->userService->getUserDetails();

        if ($userDetails !== false) {
            $event->setParam('userDetails', $userDetails);
            $this->sessionUtility->setInMvc(ContainerNamespace::USER_DETAILS, 'user', $userDetails);
        }

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);
        $routeOptions = $route ? $route->getMatchedRoute()->getOptions() : [];
        $isUnauthenticatedRoute = $route && isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true;

        if ($route === null || $isUnauthenticatedRoute === true) {
            return $handler->handle($request);
        }

        $userDetails = $this->userService->getUserDetails();

        if ($userDetails !== false) {
            $request = $request->withAttribute('userDetails', $userDetails);
            $this->sessionUtility->setInMvc(ContainerNamespace::USER_DETAILS, 'user', $userDetails);
        }

        return $handler->handle($request);
    }
}
