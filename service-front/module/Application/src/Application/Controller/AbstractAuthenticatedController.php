<?php
namespace Application\Controller;

use Application\Model\Library\Authentication\Identity\User as Identity;

class AbstractAuthenticatedController extends AbstractBaseController implements UserAwareInterface
{
    
    private $user;
    
    /**
     * @return the $user
     */
    public function getUser ()
    {
        return $this->user;
    }

    /**
     * @param Identity $user
     */
    public function setUser( Identity $user )
    {
        $this->user = $user;
    }

}
