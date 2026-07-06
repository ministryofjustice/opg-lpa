<?php

namespace App\Middleware\Authorization;

use App\RequestAttributes;
use App\Service\ApiClient\ApiException;
use Fig\Http\Message\StatusCodeInterface;
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
    public function __construct(
        private readonly UrlHelper $urlHelper,
        private readonly Rbac $rbac,
        private readonly NotFoundHandler $notFoundHandler,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $claims = $request->getAttribute(RequestAttributes::OIDC_CLAIMS);

        $roles = ['guest'];

        if (!empty($claims['sub']) && !empty($claims['email'])) {
            $roles[] = 'authenticated-user';
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
                    return $handler->handle($request->withAttribute(RequestAttributes::USER_EMAIL, $claims['email'] ?? null));
                } catch (ApiException $ae) {
                    if ($ae->getCode() === StatusCodeInterface::STATUS_UNAUTHORIZED) {
                        return new RedirectResponse($this->urlHelper->generate('sign.out'));
                    } else {
                        throw $ae;
                    }
                }
            }
        }

        // No role grants access. If unauthenticated (no claims), redirect to sign-in.
        // If authenticated but lacking permission, throw 403.
        if (empty($claims['sub'])) {
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        }

        throw new Exception('Access forbidden', StatusCodeInterface::STATUS_FORBIDDEN);
    }
}
