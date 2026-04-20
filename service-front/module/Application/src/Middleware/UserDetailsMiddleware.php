<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Session\SessionManager;
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
 * Fetches the authenticated user's details and makes them available to downstream
 * middleware and controllers. If the user's profile is incomplete (no name set),
 * they are redirected to the about-you page unless the route opts in with
 * allowIncompleteUser. If the user record cannot be reconstructed from the session,
 * the session is destroyed and the user is redirected to the login page.
 *
 * User details are stored as a request attribute keyed by RequestAttribute::USER_DETAILS.
 */
class UserDetailsMiddleware implements MiddlewareInterface, LoggerAwareInterface
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);

        // If no Mezzio RouteResult, we're in MVC hybrid mode - just fetch user details and continue
        if ($route === null) {
            $userDetails = $this->userService->getUserDetails();

            if ($userDetails !== false) {
                $request = $request->withAttribute(RequestAttribute::USER_DETAILS, $userDetails);
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

        if (!$userDetails instanceof User) {
            return $handler->handle($request);
        }

        $request = $request->withAttribute(RequestAttribute::USER_DETAILS, $userDetails);
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
