<?php

declare(strict_types=1);

namespace App\Service\ApiClient\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ConflictException extends RuntimeException
{
    private int $statusCode;

    /** @var null|array{lastUpdatedBy: string} $data */
    private mixed $data;

    public function __construct(ResponseInterface $response, ?string $message = null)
    {
        $body = json_decode(strval($response->getBody()), true);
        $this->data = json_decode($body['detail'] ?? '', true);
        $this->statusCode = $response->getStatusCode();

        parent::__construct(sprintf('Conflicts with changes by %s', $this->getLastUpdatedBy()), $this->statusCode);
    }

    public function getLastUpdatedBy(): ?string
    {
        return $this->data['lastUpdatedBy'] ?? null;
    }
}
