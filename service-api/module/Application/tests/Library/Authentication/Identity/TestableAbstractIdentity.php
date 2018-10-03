<?php

namespace Library\Authentication\Identity;

use Application\Library\Authentication\Identity\AbstractIdentity;

class TestableAbstractIdentity extends AbstractIdentity
{
    public function __construct($id, $roles)
    {
        $this->id = $id;
        $this->roles = $roles;
    }
}
