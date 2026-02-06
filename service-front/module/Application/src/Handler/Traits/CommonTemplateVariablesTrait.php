<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait CommonTemplateVariablesTrait
{
    public function getTemplateVariables(ServerRequestInterface $request): array
    {
        return [
            'signedInUser' => $request->getAttribute('signedInUser'),
            'secondsUntilSessionExpires' => $request->getAttribute('secondsUntilSessionExpires'),
        ];
    }
}
