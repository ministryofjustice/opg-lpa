<?php

namespace ApplicationTest\Controller;

class InvalidUser
{
    public $name = 'Invalid';
    public $email = 'Invalid';

    public function toArray()
    {
        return get_object_vars($this);
    }
}
