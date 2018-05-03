<?php

namespace Application\Library\Authentication\Identity;

class Guest extends AbstractIdentity
{
    protected $roles = ['guest'];
}
