<?php

namespace Application\Library\Authentication\Identity;

abstract class AbstractIdentity implements IdentityInterface
{
    protected $id = null;

    protected $roles = [];

    public function id()
    {
        return $this->id;
    }

    public function getRoles()
    {
        return $this->roles;
    }
}
