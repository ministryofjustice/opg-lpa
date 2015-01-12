<?php

namespace Application\Controller;

interface UserAwareInterface
{
    public function getUser();
    
    public function setUser($user);
    
}
