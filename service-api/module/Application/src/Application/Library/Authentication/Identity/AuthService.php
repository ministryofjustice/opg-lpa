<?php

namespace Application\Library\Authentication\Identity;

class AuthService extends AbstractIdentity
{
    public function id()
    {
        return null;
    }

    public function getRoles()
    {
        return ['service'];
    }
}
