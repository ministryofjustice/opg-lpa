<?php

namespace App\Middleware\Authorization;

use App\Handler\Traits\JwtTrait;
use App\Service\ApiClient\ApiException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Handler\NotFoundHandler;
use Zend\Permissions\Rbac\Rbac;
use Exception;

/**
 * Class AuthorizationMiddleware
 * @package App\Middleware\Authorization
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    use JwtTrait;

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
     * @param UrlHelper $urlHelper
     * @param Rbac $rbac
     * @param NotFoundHandler $notFoundHandler
     */
    public function __construct(UrlHelper $urlHelper, Rbac $rbac, NotFoundHandler $notFoundHandler)
    {
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
//TODO - Change this...
        $token = $this->getTokenData('token');

//TODO - get the roles out of the identity when this bit is wired up
        $roles = ['guest'];
        if (!is_null($token)) {
            $roles[] = 'authenticated-user';
        }

        //  Determine the route was are attempting to access
        $route = $request->getAttribute(RouteResult::class);
        $matchedRoute = $route->getMatchedRoute();

        if (!$matchedRoute) {
            return $this->notFoundHandler->handle($request);
        }

        //  Check each role to see if the user has access to the route
        foreach ($roles as $role) {
            if ($this->rbac->hasRole($role) && $this->rbac->isGranted($role, $matchedRoute->getName())) {
                //  Catch any unauthorized exceptions and trigger a sign out if required
                try {
//TODO - Pass the identity down...
//                    return $delegate->process(
//                        $request->withAttribute('identity', $identity)
//                    );
                    return $handler->handle($request);
                } catch (ApiException $ae) {
                    if ($ae->getCode() === 401) {
                        return new RedirectResponse($this->urlHelper->generate('sign.out'));
                    } else {
                        throw $ae;
                    }
                }
            }
        }

        //  If there is no identity (not logged in) then redirect to the sign in screen
//TODO - Change this when we know what identity will look like???
        if (is_null($token)) {
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        }

        //  Throw a forbidden exception
        throw new Exception('Access forbidden', 403);
    }
}
