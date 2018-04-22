<?php

namespace Application\Model\Service\ApiClient\Response;

use Application\Model\Service\ApiClient\Exception;
use Opg\Lpa\DataModel\Lpa\Lpa as BaseLpa;
use Psr\Http\Message\ResponseInterface;

class Lpa extends BaseLpa
{
    private $response;

    public static function buildFromResponse(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        // The expected response should always be JSON, thus now an array.
        if (!is_array($body)) {
            throw new Exception\ResponseException('Malformed JSON response from server', $response->getStatusCode(), $response);
        }

        $lpa = new static( $body );

        $lpa->setResponse($response);

        return $lpa;
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
