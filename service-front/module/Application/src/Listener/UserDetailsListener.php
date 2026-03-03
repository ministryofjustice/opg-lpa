<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use MakeShared\DataModel\User\User;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches user details and makes them available in the event/request/session for downstream
 * middleware and controllers to use.
 */
class UserDetailsListener extends AbstractListenerAggregate implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        protected SessionUtility $sessionUtility,
        protected Details $userService,
        protected AuthenticationService $authenticationService,
        protected SessionManager $sessionManager,
        LoggerInterface $logger,
    ) {
        $this->setLogger($logger);
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

        if ($userDetails === false) {
            return null;
        }

        $event->setParam(Attribute::USER_DETAILS, $userDetails);
        $this->sessionUtility->setInMvc(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        if (!$routeMatch->getParam('allowIncompleteUser', false) && $userDetails->getName() === null) {
            $url = $event->getRouter()->assemble(['new' => 'new'], ['name' => 'user/about-you']);

            $response = new Response();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(StatusCodeInterface::STATUS_FOUND);

            return $response;
        }

        // Validate that we have a fully formed user record
        try {
            $userDataArr = $userDetails->toArray();
            new User($userDataArr);
        } catch (\Exception $ex) {
            $this->getLogger()->error('constructing User data from session failed', ['exception' => $ex->getMessage()]);
            $this->authenticationService->clearIdentity();
            $this->sessionManager->destroy([
                'clear_storage' => true
            ]);

            $url = $event->getRouter()->assemble(['state' => 'timeout'], ['name' => 'login']);

            $response = new Response();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(StatusCodeInterface::STATUS_FOUND);

            return $response;
        }

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);

        // If no Mezzio RouteResult, we're in MVC hybrid mode - just fetch user details and continue
        if ($route === null) {
            $userDetails = $this->userService->getUserDetails();

            if ($userDetails !== false) {
                $request = $request->withAttribute(Attribute::USER_DETAILS, $userDetails);
                $this->sessionUtility->setInMvc(ContainerNamespace::USER_DETAILS, 'user', $userDetails);
            }

            return $handler->handle($request);
        }

        // Mezzio routing logic below
        $routeOptions = $route->getMatchedRoute()->getOptions();
        $isUnauthenticatedRoute = isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true;

        if ($isUnauthenticatedRoute === true) {
            return $handler->handle($request);
        }

        $userDetails = $this->userService->getUserDetails();

        if ($userDetails === false) {
            return $handler->handle($request);
        }

        $request = $request->withAttribute(Attribute::USER_DETAILS, $userDetails);
        $this->sessionUtility->setInMvc(ContainerNamespace::USER_DETAILS, 'user', $userDetails);

        $allowIncompleteUser = isset($routeOptions['allowIncompleteUser']) && $routeOptions['allowIncompleteUser'] === true;
        if (!$allowIncompleteUser && $userDetails->getName() === null) {
            return new RedirectResponse('/user/about-you/new');
        }

        // Validate that we have a fully formed user record
        try {
            $userDataArr = $userDetails->toArray();
            new User($userDataArr);
        } catch (\Exception $ex) {
            $this->getLogger()->error('constructing User data from session failed', ['exception' => $ex->getMessage()]);
            $this->authenticationService->clearIdentity();
            $this->sessionManager->destroy([
                'clear_storage' => true
            ]);

            return new RedirectResponse('/login?state=timeout');
        }

        return $handler->handle($request);
    }
}
