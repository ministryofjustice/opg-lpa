<?php
namespace Opg\Lpa\Api\Client\Exception;

use Psr\Http\Message\ResponseInterface;

class ResponseException extends RuntimeException {

    private $response;

    public function __construct($message, $code, ResponseInterface $response) {

        $this->response = $response;

        parent::__construct( $message, $code );

    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(){
        return $this->response;
    }

}
