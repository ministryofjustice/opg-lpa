<?php

namespace Alphagov\Pay\Response;

use Alphagov\Pay\Exception;
use Psr\Http\Message\ResponseInterface;

trait IncludeResponseTrait
{
    private $response;

    //---

    public static function buildFromResponse(ResponseInterface $response)
    {

        $body = (array)json_decode($response->getBody());

        // The expected response should always be JSON, thus now an array.
        if (!is_array($body)) {
            throw new Exception\ApiException('Malformed JSON response from server', $response->getStatusCode(), $response);
        }

        $payment = new static($body);

        $payment->setResponse($response);

        return $payment;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
