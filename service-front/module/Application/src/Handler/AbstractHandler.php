<?php

declare(strict_types=1);

namespace Application\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractHandler implements RequestHandlerInterface
{
    public function __construct(
        protected TemplateRendererInterface $renderer,
    ) {
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function handle(ServerRequestInterface $request): ResponseInterface;
}
