<?php

namespace Library\Authentication\Identity;

use Application\Library\Authentication\Identity\IdentityAwareTrait;
use Application\Library\Authentication\Identity\IdentityInterface;

class TestableIdentityAwareTrait
{
    use IdentityAwareTrait;

    public function __construct(?IdentityInterface $id)
    {
        $this->identity = $id;
    }
}
