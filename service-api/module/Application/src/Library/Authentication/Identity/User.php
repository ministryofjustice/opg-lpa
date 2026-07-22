<?php

namespace Application\Library\Authentication\Identity;

class User extends AbstractIdentity
{
    protected $roles = ['user'];

    protected $email;

    public function __construct($userId, $email)
    {
        $this->id = $userId;
        $this->email = $email;
    }

    public function email()
    {
        return $this->email;
    }
}
