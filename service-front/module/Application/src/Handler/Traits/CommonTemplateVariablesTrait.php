<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Application\Listener\Attribute;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

trait CommonTemplateVariablesTrait
{
    public function getTemplateVariables(ServerRequestInterface $request): array
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $routeName = $routeMatch instanceof RouteMatch ? $routeMatch->getMatchedRouteName() : null;

        return [
            'signedInUser' => $request->getAttribute(Attribute::USER_DETAILS),
            'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
            'currentRouteName' => $routeName,
        ];
    }
}
