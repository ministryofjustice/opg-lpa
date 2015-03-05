<?php
namespace Application\Controller;

use DateTime;
use RuntimeException;

use Zend\Mvc\MvcEvent;
use Zend\Session\Container as SessionContainer;
use Application\Model\Service\Authentication\Identity\User as Identity;

use Application\Controller\Authenticated\AboutYouController;

abstract class AbstractAuthenticatedController extends AbstractBaseController implements UserAwareInterface
{
    /**
     * @var Identity The Identity of the current authenticated user.
     */
    private $user;

    /**
     * If a controller is excluded from the ABout You check, this should be overridden to TRUE.
     *
     * @var bool Is this controller excluded form checking if the About You section is complete.
     */
    protected $excludeFromAboutYouCheck = false;


    /**
     * Do some pre-dispatch checks...
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e){

        // Before the user can access any actions that extend this controller...

        //----------------------------------------------------------------------
        // Check we have a user set, thus ensuring an authenticated user

        if( !( $this->user instanceof Identity ) ){
            die('Not logged in / timed out! This will redirect to the login page.');
        }

        $identity = $this->getServiceLocator()->get('AuthenticationService')->getIdentity();


        //----------------------------------------------------------------------
        // Check if they've singed in since the T&C's changed...

        /*
         * We check here if the terms have changed since the user last logged in.
         * We also use a session to record whether the user has seen the 'Terms have changed' page since logging in.
         *
         * If the terms have changed and they haven't seen the 'Terms have changed' page
         * in this session, we redirect them to it.
         */

        $termsUpdated = new DateTime($this->config()['terms']['lastUpdated']);

        if( $identity->lastLogin() < $termsUpdated ){

            $termsSession = new SessionContainer('TermsAndConditionsCheck');

            if( !isset($termsSession->seen) ){

                // Flag that the 'Terms have changed' page will now have been seen...
                $termsSession->seen = true;

                return $this->redirect()->toRoute( 'user/dashboard/terms-changed' );

            } // if

        } // if


        //----------------------------------------------------------------------
        // Load the user's details and ensure the required details are included

        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');

        if( !isset($detailsContainer->user) || is_null($detailsContainer->user->name) ){

            $userDetails = $this->getServiceLocator()->get('AboutYouDetails')->load();

            // If the user details do not at least have a name
            // And we're not trying to set the details via the AboutYouController...
            if( is_null($userDetails->name) && $this->excludeFromAboutYouCheck !== true ) {

                // Redirect to the About You page.
                return $this->redirect()->toRoute( 'user/about-you/new' );

            }

            // Store the details in the session...
            $detailsContainer->user = $userDetails;

        } // if

        //---

        return parent::onDispatch( $e );

    } // function

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
     * Returns extra details about the user.
     *
     * @return mixed|null
     */
    public function getUserDetails(){

        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');

        if( !isset($detailsContainer->user) ){
            return null;
        }

        return $detailsContainer->user;

    }

    /**
     * Returns an instance of the LPA Application Service.
     *
     * @return object
     */
    protected function getLpaApplicationService(){
        return $this->getServiceLocator()->get('LpaApplicationService');
    }

} // class
