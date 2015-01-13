<?php
namespace Opg\Lpa\Api\Client\Common\Guzzle;

use GuzzleHttp\Client as GuzzleClient;

/**
 * Guzzle Client with our own defaults.
 *
 * Class Client
 * @package Opg\Lpa\Api\Client\Common\Guzzle
 */
class Client extends GuzzleClient {

    public function __construct(array $config = []){

        parent::__construct( $config );

        $this->setDefaultOption( 'exceptions', false );
        $this->setDefaultOption( 'allow_redirects', false );

    }

    /**
     * Sets the token as a default header value for the client.
     *
     * @param $token
     */
    public function setToken( $token ){

        $this->setDefaultOption( 'headers/X-AuthOne', $token );

    }

    /**
     * Clears the default header token.
     */
    public function clearToken(){

        $this->setToken( null );

    }

} // class
