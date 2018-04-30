<?php

namespace Application\Model\Service\AuthClient\Exception;

use Psr\Http\Message\ResponseInterface;

class ResponseException extends \RuntimeException
{
    private $response;

    public function __construct($message, $code, ResponseInterface $response)
    {
        $this->response = $response;

        parent::__construct($message, $code);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function getDetail()
    {
        $body = json_decode($this->getResponse()->getBody(), true);

        if (is_array($body) && isset($body['detail'])) {
            return $body['detail'];
        }

        return $this->getMessage();
    }
}
