<?php

namespace Application\Library\Authentication\Identity;

use LmcRbacMvc\Identity\IdentityInterface as LmcRbacIdentityInterface;

interface IdentityInterface extends LmcRbacIdentityInterface
{
    /**
     * @psalm-api
     */
    public function id();
}
