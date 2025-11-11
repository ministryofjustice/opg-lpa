<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PingHandlerElb implements RequestHandlerInterface
{
    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse('Happy face');
    }
}
