<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Attribute;

use JsonSerializable;
use Laminas\Http\Request;
use Laminas\Http\Response;

class Http implements JsonSerializable
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
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
