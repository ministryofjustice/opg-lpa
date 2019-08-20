<?php
namespace Application\Library\Authentication\Identity;

use RuntimeException;

/**
 * Enables Identity setting for a class.
 * Ensures a class complies with IdentityAwareInterface.
 *
 * Class IdentityAwareTrait
 * @package Application\Library\Authentication\Identity
 */
trait IdentityAwareTrait {

    /**
     * @var IdentityInterface The current user's identity.
     */
    protected $identity;

    /**
     * @param IdentityInterface $identity Set the current user's identity.
     */
    public function setIdentity( IdentityInterface $identity ){
        $this->identity = $identity;
    }

    /**
     * @return IdentityInterface Get the current user's identity.
     * @throws RuntimeException If identity is not set.
     */
    public function getIdentity(){

        if( !isset($this->identity) || !( $this->identity instanceof IdentityInterface ) ){
            throw new RuntimeException('No identity set');
        }

        return $this->identity;

    }

} // trait
