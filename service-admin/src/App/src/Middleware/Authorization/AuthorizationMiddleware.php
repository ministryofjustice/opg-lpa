<?php

namespace App\Middleware\Authorization;

use App\Handler\Traits\JwtTrait;
use App\Service\ApiClient\ApiException;
use App\Service\Authentication\AuthenticationService;
use App\Service\Authentication\Identity;
use App\Service\User\UserService;
use MakeShared\DataModel\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Handler\NotFoundHandler;
use Laminas\Permissions\Rbac\Rbac;
use Exception;

/**
 * Class AuthorizationMiddleware
 * @package App\Middleware\Authorization
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    use JwtTrait;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var Rbac
     */
    private $rbac;

    /**
     * @var NotFoundHandler
     */
    private $notFoundHandler;

    /**
     * AuthorizationMiddleware constructor.
     *
     * @param AuthenticationService $authenticationService
     * @param UserService $userService
     * @param UrlHelper $urlHelper
     * @param Rbac $rbac
     * @param NotFoundHandler $notFoundHandler
     */
    public function __construct(
        AuthenticationService $authenticationService,
        UserService $userService,
        UrlHelper $urlHelper,
        Rbac $rbac,
        NotFoundHandler $notFoundHandler
    ) {
        $this->authenticationService = $authenticationService;
        $this->userService = $userService;
        $this->urlHelper = $urlHelper;
        $this->rbac = $rbac;
        $this->notFoundHandler = $notFoundHandler;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->getTokenData('token');

        $user = null;

        $roles = ['guest'];

        if (is_string($token)) {
            // Attempt to get a user with the token value
            $result = $this->authenticationService->verify($token);

            $identity = $result->getIdentity();

            if ($identity instanceof Identity) {
                // Try to get the user details
                $user = $this->userService->fetch($identity->getUserId() ?? '');

                // There is something wrong with the user here so throw an exception
                if (!$user instanceof User) {
                    throw new Exception('Can not find a user for ID ' . $identity->getUserId());
                }

                $roles[] = 'authenticated-user';
            } else {
                // Clear the bad token
                $this->clearTokenData();
            }
        }

        //  Determine the route we are attempting to access
        /** @var RouteResult $route */
        $route = $request->getAttribute(RouteResult::class);
        $matchedRoute = $route->getMatchedRoute();

        if (!$matchedRoute) {
            return $this->notFoundHandler->handle($request);
        }

        //  Check each role to see if the user has access to the route
        foreach ($roles as $role) {
            if ($this->rbac->hasRole($role) && $this->rbac->isGranted($role, $matchedRoute->getName())) {
                // Catch any unauthorized exceptions and trigger a sign out if required
                try {
                    return $handler->handle($request->withAttribute('user', $user));
                } catch (ApiException $ae) {
                    if ($ae->getCode() === 401) {
                        return new RedirectResponse($this->urlHelper->generate('sign.out'));
                    } else {
                        throw $ae;
                    }
                }
            }
        }

        // If there is no user (not logged in) then redirect to the sign in screen
        if (is_null($user)) {
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        }

        //  Throw a forbidden exception
        throw new Exception('Access forbidden', 403);
    }
}
