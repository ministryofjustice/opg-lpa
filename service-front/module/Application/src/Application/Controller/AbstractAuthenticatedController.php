<?php

namespace Application\Controller;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\User\Details as UserService;
use Opg\Lpa\DataModel\User\User;
use Zend\Authentication\AuthenticationService;
use Zend\Cache\Storage\StorageInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\Session\AbstractContainer;
use Zend\Session\Container as SessionContainer;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use DateTime;
use RuntimeException;

abstract class AbstractAuthenticatedController extends AbstractBaseController
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
     * @var AbstractContainer
     */
    private $userDetailsSession;

    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var AdapterInterface
     */
    private $authenticationAdapter;

    /**
     * AbstractAuthenticatedController constructor.
     * @param AbstractPluginManager $formElementManager
     * @param SessionManager $sessionManager
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param StorageInterface $cache
     * @param AbstractContainer $userDetailsSession
     * @param LpaApplicationService $lpaApplicationService
     * @param UserService $userService
     * @param AdapterInterface $authenticationAdapter
     */
    public function __construct(
        AbstractPluginManager $formElementManager,
        SessionManager $sessionManager,
        AuthenticationService $authenticationService,
        array $config,
        StorageInterface $cache,
        AbstractContainer $userDetailsSession,
        LpaApplicationService $lpaApplicationService,
        UserService $userService,
        AdapterInterface $authenticationAdapter
    ) {
        parent::__construct($formElementManager, $sessionManager, $authenticationService, $config, $cache);

        $this->userDetailsSession = $userDetailsSession;
        $this->lpaApplicationService = $lpaApplicationService;
        $this->userService = $userService;
        $this->authenticationAdapter = $authenticationAdapter;
    }

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

        if( ($authenticated = $this->checkAuthenticated()) !== true ){
            return $authenticated;
        }

        $identity = $this->getAuthenticationService()->getIdentity();

        $this->getLogger()->info('Request to ' . get_class($this), $identity->toArray());

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

        $detailsContainer = $this->userDetailsSession;

        //  Check to see if the user object is present and well formed
        if (isset($detailsContainer->user)) {
            //  To check this simply try to take the data from one array and populate it into another object
            try {
                $userDataArr = $detailsContainer->user->toArray();
                $tempUser = new User($userDataArr);
            } catch (\Exception $ex) {
                //  If seems there is a user associated with the session but it is not well formed
                //  Therefore destroy the session and logout the user
                $this->getAuthenticationService()->clearIdentity();
                $this->getSessionManager()->destroy([
                    'clear_storage' => true
                ]);

                return $this->redirect()->toRoute('login', [
                    'state' => 'timeout'
                ]);
            }
        }

        if (!isset($detailsContainer->user) || is_null($detailsContainer->user->name)) {
            $userDetails = $this->userService->load();

            // If the user details do not at least have a name
            // And we're not trying to set the details via the AboutYouController...
            if( is_null($userDetails->name) && $this->excludeFromAboutYouCheck !== true ) {

                // Redirect to the About You page.
                return $this->redirect()->toUrl('/user/about-you/new');
            }

            // Store the details in the session...
            $detailsContainer->user = $userDetails;

        } // if

        //---

        // inject lpa into view
        $view = parent::onDispatch($e);

        if(($view instanceof ViewModel) && !($view instanceof JsonModel)) {
            $view->setVariable('signedInUser', $this->getUserDetails());
        }

        return $view;

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

        $detailsContainer = $this->userDetailsSession;

        if( !isset($detailsContainer->user) ){
            return null;
        }

        return $detailsContainer->user;

    }

    /**
     * @return AbstractContainer
     */
    protected function getUserDetailsSession(): AbstractContainer
    {
        return $this->userDetailsSession;
    }

    /**
     * Returns an instance of the LPA Application Service.
     *
     * @return LpaApplicationService
     */
    protected function getLpaApplicationService(): LpaApplicationService
    {
        return $this->lpaApplicationService;
    }

    /**
     * @return UserService
     */
    protected function getUserService(): UserService
    {
        return $this->userService;
    }

    /**
     * @return AdapterInterface
     */
    protected function getAuthenticationAdapter(): AdapterInterface
    {
        return $this->authenticationAdapter;
    }

    /**
     * Check there is a user authenticated.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated( $allowRedirect = true ){

        if( !( $this->user instanceof Identity ) ){

            if( $allowRedirect ){

                $preAuthRequest = new Container('PreAuthRequest');

                $preAuthRequest->url = (string)$this->getRequest()->getUri();

            }

            //---

            // Redirect to the About You page.
            return $this->redirect()->toRoute( 'login', [ 'state'=>'timeout' ] );

        } // if

        return true;

    } // function

    /**
     * delete cloned data for this seed id from session container if it exists.
     * to make sure clone data will be loaded freshly when actor form is rendered.
     *
     * @param int $seedId
     */
    protected function resetSessionCloneData($seedId)
    {
        $cloneContainer = new Container('clone');
        if($cloneContainer->offsetExists($seedId)) {
            unset($cloneContainer->$seedId);
        }
    }

} // class
