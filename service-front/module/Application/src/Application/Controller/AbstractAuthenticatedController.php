<?php
namespace Application\Controller;

use Application\Model\Library\Authentication\Identity\User as Identity;

abstract class AbstractAuthenticatedController extends AbstractBaseController implements UserAwareInterface
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

    /**
     * Returns an instance of the LPA Application Service.
     *
     * @return object
     */
    protected function getLpaApplicationService(){
        return $this->getServiceLocator()->get('LpaApplicationService');
    }

}
