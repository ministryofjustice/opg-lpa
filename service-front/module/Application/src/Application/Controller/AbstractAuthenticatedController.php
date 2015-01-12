<?php

namespace Application\Controller;

use Application\Controller\AbstractBaseController;

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
     * @param field_type $user
     */
    public function setUser ($user)
    {
        $this->user = $user;
    }

}
