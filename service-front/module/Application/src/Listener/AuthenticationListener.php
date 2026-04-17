<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as MVCResponse;

/**
 * Checks that the user is authenticated before allowing access to protected routes,
 * redirecting to the login page with an appropriate reason if not.
 */
class AuthenticationListener extends AbstractListenerAggregate
{
    private const string REASON_TIMEOUT = 'timeout';
    private const string REASON_INTERNAL_SYSTEM_ERROR = 'internalSystemError';

    public function __construct(
        private readonly SessionUtility $sessionUtility,
        private readonly AuthenticationService $authenticationService,
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
