<?php

declare(strict_types=1);

namespace App\Service\ApiClient\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ConflictException extends RuntimeException
{
    private readonly int $statusCode;
    private readonly string $lastUpdatedBy;

    public function __construct(ResponseInterface $response, ?string $message = null)
    {
        $body = json_decode(strval($response->getBody()), true);
        $this->lastUpdatedBy = $body['lastUpdatedBy'];
        $this->statusCode = $response->getStatusCode();

        parent::__construct('Conflict', $this->statusCode);
    }

    public function getLastUpdatedBy(): string
    {
        return $this->lastUpdatedBy;
    }
}
