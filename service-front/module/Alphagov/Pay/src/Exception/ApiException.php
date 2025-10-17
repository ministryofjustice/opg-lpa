<?php

namespace Alphagov\Pay\Exception;

use Psr\Http\Message\ResponseInterface;

class ApiException extends PayException
{
    private $response;

    public function __construct($message = "", $code = 0, ResponseInterface $response)
    {

        $this->response = $response;

        parent::__construct($message, $code);
    }
}
