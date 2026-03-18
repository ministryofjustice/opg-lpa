<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Application\Middleware\RequestAttribute;
use Psr\Http\Message\ServerRequestInterface;

trait CommonTemplateVariablesTrait
{
    public function getTemplateVariables(ServerRequestInterface $request): array
    {
        return [
            'signedInUser' => $request->getAttribute(RequestAttribute::USER_DETAILS),
            'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
            'lpa' => $request->getAttribute(RequestAttribute::LPA),
            'currentRouteName' => $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME),
        ];
    }
}
