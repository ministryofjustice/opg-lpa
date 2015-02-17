<?php
namespace Application\Controller;

use Application\Model\Service\Authentication\Identity\User as Identity;

interface UserAwareInterface
{
    public function getUser();
    
    public function setUser( Identity $user );
    
}
