<?php
namespace Application\Model\Service\Authentication;

use DateTime;

# TODO - delete this.

use Zend\Authentication\AuthenticationService as ZFAuthenticationService;

class AuthenticationService extends ZFAuthenticationService {

    public function __construct(){
        throw new \RuntimeException('Deprecated. If you get this tell Neil!');
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return bool
     */
    public function hasIdentity()
    {
        if( $this->getStorage()->isEmpty() ){
            return false;
        }

        // Check the token is still valid.
        $ident = $this->getStorage()->read();

        if( (new DateTime) > $ident->tokenExpiresAt() ){
            // Check token is still valid...
        }

        return true;

    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity(){

        if (!$this->hasIdentity()) {
            return null;
        }

        return $this->getStorage()->read();

    }

} // class
