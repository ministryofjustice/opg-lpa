<?php
namespace Application\Model\Service\Authentication;

use Zend\Authentication\AuthenticationService as ZFAuthenticationService;

/**
 * Used to enforce the setAdapter method to take our own AdapterInterface.
 *
 * Class AuthenticationService
 * @package Application\Model\Service\Authentication
 */
class AuthenticationService extends ZFAuthenticationService {

    /**
     * Sets the authentication adapter
     *
     * @param  Adapter\AdapterInterface $adapter
     * @return AuthenticationService Provides a fluent interface
     */
    public function setAdapter(Adapter\AdapterInterface $adapter){
        return parent::setAdapter( $adapter );
    }

} // class
