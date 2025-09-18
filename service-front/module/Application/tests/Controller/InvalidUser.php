<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

class InvalidUser
{
    public $name = 'Invalid';
    public $email = 'Invalid';

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
