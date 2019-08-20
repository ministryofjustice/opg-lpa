<?php
namespace Application\Library\Authentication\Identity;

use ZfcRbac\Identity\IdentityInterface as ZfcRbacIdentityInterface;

interface IdentityInterface extends ZfcRbacIdentityInterface {

    public function id();

} // interface
