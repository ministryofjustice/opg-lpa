<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ResponseInterface as MVCResponse;
use MakeShared\DataModel\User\User;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches the authenticated user's details and makes them available to downstream
 * middleware and controllers. If the user's profile is incomplete (no name set),
 * they are redirected to the about-you page unless the route opts in with
 * allowIncompleteUser. If the user record cannot be reconstructed from the session,
 * the session is destroyed and the user is redirected to the login page.
 */
class UserDetailsListener extends AbstractListenerAggregate implements LoggerAwareInterface
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

        $event->setParam(EventParameter::USER_DETAILS, $userDetails);
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
}
