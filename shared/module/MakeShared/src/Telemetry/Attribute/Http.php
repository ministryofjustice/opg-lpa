<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Attribute;

use JsonSerializable;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class Http implements JsonSerializable
{
    public function __construct(
        private readonly Request|RequestInterface $request,
        private readonly Response|ResponseInterface $response
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'request' => [
                'method' => $this->request->getMethod(),
                'url' => $this->request->getUri()->__toString(),
            ],
            'response' => [
                'status' => $this->response->getStatusCode(),
            ],
        ];
    }
}
