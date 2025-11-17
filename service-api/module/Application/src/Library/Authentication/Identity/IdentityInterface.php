<?php

namespace Application\Library\Authentication\Identity;

use Lmc\Rbac\Mvc\Identity\IdentityInterface as LmcRbacIdentityInterface;

interface IdentityInterface extends LmcRbacIdentityInterface
{
    /**
     * @psalm-api
     */
    public function id();
}
