<?php

declare(strict_types=1);

namespace App\Middleware;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use App\Model\UserDetailsHolder;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\User\User;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Mezzio port of Application\Middleware\UserDetailsMiddleware.
 *
 * Fetches the authenticated user's details from the API and stores them on
 * the request as RequestAttribute::USER_DETAILS. Redirects to /user/about-you/new
 * if the user's profile is incomplete, or to /login?state=timeout if the user
 * record is corrupt/cannot be reconstructed.
 *
 * MVC session concerns (SessionManager, SessionUtility, ContainerNamespace) are
 * dropped — Mezzio handlers read user details directly from the request attribute.
 *
 * Must run after AuthenticationMiddleware (which ensures the identity is set).
 */
class UserDetailsMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly UserService $userService,
        private readonly AuthenticationService $authenticationService,
        private readonly UserDetailsHolder $userDetailsHolder,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);

        if (!$route instanceof RouteResult) {
            return $handler->handle($request);
        }

        $matchedRoute = $route->getMatchedRoute();
        $routeOptions = $matchedRoute !== false ? ($matchedRoute->getOptions() ?: []) : [];

        if (isset($routeOptions['unauthenticated_route']) && $routeOptions['unauthenticated_route'] === true) {
            return $handler->handle($request);
        }

        $userDetails = $this->userService->getUserDetails();

        if (!$userDetails instanceof User) {
            return $handler->handle($request);
        }

        $request = $request->withAttribute(RequestAttribute::USER_DETAILS, $userDetails);
        $this->userDetailsHolder->set($userDetails);

        $allowIncompleteUser = isset($routeOptions['allowIncompleteUser']) && $routeOptions['allowIncompleteUser'] === true;
        if (!$allowIncompleteUser && $userDetails->getName() === null) {
            return new RedirectResponse('/user/about-you/new');
        }

        // Validate that we have a fully-formed user record
        try {
            $userDataArr = $userDetails->toArray();
            new User($userDataArr);
        } catch (\Exception $ex) {
            $this->getLogger()->error('constructing User data from session failed', ['exception' => $ex->getMessage()]);

            $this->authenticationService->clearIdentity();

            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            if ($session instanceof SessionInterface) {
                $session->clear();
                $session->regenerate();
            }

            return new RedirectResponse('/login?state=timeout');
        }

        return $handler->handle($request);
    }
}
