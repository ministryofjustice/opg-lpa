<?php
namespace Application\Library\Authentication\Identity;

interface IdentityAwareInterface {

    /**
     * @param IdentityInterface $identity Set the current user's identity.
     */
    public function setIdentity( IdentityInterface $identity );

    /**
     * @return IdentityInterface Get the current user's identity.
     * @throws \RuntimeException If identity is not set.
     */
    public function getIdentity();

} // interface
