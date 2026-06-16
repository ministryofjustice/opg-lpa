<?php

declare(strict_types=1);

namespace App\Service\Payment\GovPay\Exception;

use Psr\Http\Message\ResponseInterface;

class ApiException extends PayException
{
    public function __construct(
        string $message,
        int $code,
        private readonly ResponseInterface $response,
    ) {
        parent::__construct($message, $code);
    }

    public function getApiResponse(): ResponseInterface
    {
        return $this->response;
    }
}
