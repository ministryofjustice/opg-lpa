<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\System\Status;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PingHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Status $statusService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_encode(['status' => $this->statusService->check()]);
        if ($body === false) {
            throw new RuntimeException('could not marshal JSON', 0);
        }

        return new HtmlResponse($body);
    }
}
