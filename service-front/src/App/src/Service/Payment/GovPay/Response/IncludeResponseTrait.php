<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception;
use Psr\Http\Message\ResponseInterface;

trait IncludeResponseTrait
{
    private ?ResponseInterface $response = null;

    public static function buildFromResponse(ResponseInterface $response): static
    {
        try {
            $decoded = json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new Exception\ApiException(
                'Malformed JSON response from server',
                $response->getStatusCode(),
                $response
            );
        }

        $body     = (array) $decoded;
        $instance = new static($body);
        $instance->setResponse($response);

        return $instance;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
