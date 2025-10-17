<?php

namespace Application\Library\Authentication\Identity;

use LmcRbacMvc\Identity\IdentityInterface as LmcRbacIdentityInterface;

interface IdentityInterface extends LmcRbacIdentityInterface
{
    public function id();
} // interface
