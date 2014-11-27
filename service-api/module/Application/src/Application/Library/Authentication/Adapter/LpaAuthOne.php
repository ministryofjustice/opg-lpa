<?php
namespace Application\Library\Authentication\Adapter;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception\ExceptionInterface;

/**
 * Authentication adapter for Version 1 of the LPA auth service.
 *
 * Class LpaAuthOne
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuthOne implements AdapterInterface {

    private $token;

    /**
     * Sets username and password for authentication
     *
     * @return void
     */
    public function __construct( $token ){
        $this->token = $token;
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *               If authentication cannot be performed
     */
    public function authenticate(){


        // Don't leave this lying around...
        unset($this->token);
    }

} // class
