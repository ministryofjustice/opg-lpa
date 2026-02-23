<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Application\Listener\Attribute;
use Psr\Http\Message\ServerRequestInterface;

trait CommonTemplateVariablesTrait
{
    public function getTemplateVariables(ServerRequestInterface $request): array
    {
        return [
            // Template expects 'signedInUser', request attribute is 'userDetails'
            'signedInUser' => $request->getAttribute(Attribute::USER_DETAILS),
            'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
        ];
    }
}
