<?php

namespace Application\Library\Authentication\Identity;

interface IdentityInterface
{
    public function id();

    public function getRoles(): iterable;
}
