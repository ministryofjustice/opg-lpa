<?php
namespace MinistryOfJustice\PostcodeInfo\Response;

use MinistryOfJustice\PostcodeInfo\Exception;
use Psr\Http\Message\ResponseInterface;

trait IncludeResponseTrait {

    private $response;
    
    //---

    public static function buildFromResponse( ResponseInterface $response ){

        $body = json_decode($response->getBody(), true);

        // The expected response should always be JSON, thus now an array.
        if( !is_array($body) ){
            throw new Exception\ApiException( 'Malformed JSON response from server', $response->getStatusCode(), $response );
        }

        $me = new static( $body );

        $me->setResponse( $response );

        return $me;

    }

    private function setResponse( ResponseInterface $response ){
        $this->response = $response;
    }

    public function getResponse(){
        return $this->response;
    }

}

