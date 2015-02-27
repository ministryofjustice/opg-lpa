<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;

use Application\Model\Service\Authentication\Identity\User as Identity;

abstract class AbstractAuthenticatedController extends AbstractBaseController implements UserAwareInterface
{
    /**
     * @var Identity The Identity of the current authenticated user.
     */
    private $user;

    /**
     * Ensure we have a valid user before dispatching the acton.
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e){

        // A user *must* have been set before we dispatch the request.
        if( !( $this->user instanceof Identity ) ){
            die('Not logged in / timed out! This will redirect to the login page.');
        }

        // The user must also have set their About Me details

        return parent::onDispatch( $e );
    }

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
