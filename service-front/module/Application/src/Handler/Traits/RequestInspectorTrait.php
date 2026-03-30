<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait RequestInspectorTrait
{
    public function isXmlHttpRequest(ServerRequestInterface $request): bool
    {
        return strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }
}
