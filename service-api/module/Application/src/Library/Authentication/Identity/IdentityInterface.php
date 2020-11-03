<?php
namespace Application\Library\Authentication\Identity;

use LmcRbacMvc\Identity\IdentityInterface as ZfcRbacIdentityInterface;

interface IdentityInterface extends ZfcRbacIdentityInterface {

    public function id();

} // interface
