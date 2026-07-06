<?php

declare(strict_types=1);

namespace App\Service\ApiClient\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ApiException extends RuntimeException
{
    private int $statusCode;
    private mixed $body;

    public function __construct(ResponseInterface $response, ?string $message = null)
    {
        $this->body = json_decode(strval($response->getBody()), true);
        $this->statusCode = $response->getStatusCode();

        if (is_null($message)) {
            $message = $this->getBodyData('detail');

            if (is_null($message)) {
                $message = 'HTTP:' . $this->statusCode . ' - Unexpected API response';
            }
        }

        parent::__construct($message, $this->statusCode);
    }

    public function getTitle(): mixed
    {
        return $this->getBodyData('title');
    }

    public function getData(?string $key = null): mixed
    {
        $data = $this->getBodyData('data');

        if (!is_null($key) && is_array($data) && isset($data[$key])) {
            $data = $data[$key];
        }

        return $data;
    }

    public function getBody(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->getBodyData($key);
        }

        return $this->body;
    }

    private function getBodyData(string $key): mixed
    {
        if (is_array($this->body) && isset($this->body[$key])) {
            return $this->body[$key];
        }

        return null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
