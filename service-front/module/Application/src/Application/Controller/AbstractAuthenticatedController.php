<?php
namespace Application\Controller;

use RuntimeException;

use Application\Model\Service\Authentication\Identity\User as Identity;

abstract class AbstractAuthenticatedController extends AbstractBaseController implements UserAwareInterface
{
    /**
     * @var Identity The Identity of the current authenticated user.
     */
    private $user;


    /**
     * Return the Identity of the current authenticated user.
     *
     * @return Identity
     */
    public function getUser ()
    {
        if( !( $this->user instanceof Identity ) ){
            throw new RuntimeException('A valid Identity has not been set');
        }
        return $this->user;
    }

    /**
     * Set the Identity of the current authenticated user.
     *
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
