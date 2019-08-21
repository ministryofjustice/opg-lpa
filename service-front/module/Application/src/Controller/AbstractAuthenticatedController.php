<?php

namespace Application\Controller;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\User\Details as UserService;
use Opg\Lpa\DataModel\User\User;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use DateTime;

abstract class AbstractAuthenticatedController extends AbstractBaseController
{
    /**
     * Identity of the logged in user
     *
     * @var Identity
     */
    private $identity;

    /**
     * User details of the logged in user
     *
     * @var User
     */
    private $user;

    /**
     * Flag to indicate if complete user details are required when accessing this controller
     * Override in descendant if required
     *
     * @var bool
     */
    protected $requireCompleteUserDetails = true;

    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * AbstractAuthenticatedController constructor
     *
     * @param AbstractPluginManager $formElementManager
     * @param SessionManager $sessionManager
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param Container $userDetailsSession
     * @param LpaApplicationService $lpaApplicationService
     * @param UserService $userService
     */
    public function __construct(
        AbstractPluginManager $formElementManager,
        SessionManager $sessionManager,
        AuthenticationService $authenticationService,
        array $config,
        Container $userDetailsSession,
        LpaApplicationService $lpaApplicationService,
        UserService $userService
    ) {
        parent::__construct($formElementManager, $sessionManager, $authenticationService, $config);

        $this->lpaApplicationService = $lpaApplicationService;
        $this->userService = $userService;

        //  If there is a user identity set up the user - if this is missing the request will be bounced in the onDispatch function
        if ($authenticationService->hasIdentity()) {
            $this->identity = $authenticationService->getIdentity();

            //  Try to get the user details for this identity - look in the session first
            $user = $userDetailsSession->user;

            if (!$user instanceof User) {
                $user = $this->userService->getUserDetails();
                $userDetailsSession->user = $user;
            }

            $this->user = $user;
        }
    }

    /**
     * Do some pre-dispatch checks...
     *
     * @param MvcEvent $e
     * @return bool|mixed|\Zend\Http\Response
     * @throws \Exception
     */
    public function onDispatch(MvcEvent $e)
    {
        // Before the user can access any actions that extend this controller...

        //----------------------------------------------------------------------
        // Check we have a user set, thus ensuring an authenticated user

        if (($authenticated = $this->checkAuthenticated()) !== true) {
            return $authenticated;
        }

        $this->getLogger()->info('Request to ' . get_class($this), $this->identity->toArray());

        //----------------------------------------------------------------------
        // Check if they've signed in since the T&C's changed...

        /*
         * We check here if the terms have changed since the user last logged in.
         * We also use a session to record whether the user has seen the 'Terms have changed' page since logging in.
         *
         * If the terms have changed and they haven't seen the 'Terms have changed' page
         * in this session, we redirect them to it.
         */
        $termsUpdated = new DateTime($this->config()['terms']['lastUpdated']);

        if ($this->identity->lastLogin() < $termsUpdated) {
            $termsSession = new Container('TermsAndConditionsCheck');

            if (!isset($termsSession->seen)) {
                // Flag that the 'Terms have changed' page will now have been seen...
                $termsSession->seen = true;

                return $this->redirect()->toRoute('user/dashboard/terms-changed');
            }
        }

        //  If there are no user details set, or they are incomplete, then redirect to the about you new view
        if ($this->requireCompleteUserDetails && (!($this->user instanceof User) || is_null($this->user->name))) {
            return $this->redirect()->toUrl('/user/about-you/new');
        }

        //  We should have a fully formed user record at this point - bounce the request if that is not the case
        //  To check this simply try to take the data from one array and populate it into another object
        try {
            $userDataArr = $this->user->toArray();
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

        //  Inject the user into the view parameters
        $view = parent::onDispatch($e);

        if ($view instanceof ViewModel && !$view instanceof JsonModel) {
            $view->setVariable('signedInUser', $this->user);
            $view->setVariable(
                'secondsUntilSessionExpires',
                $this->identity->tokenExpiresAt()->getTimestamp() - (new DateTime())->getTimestamp()
            );
        }

        return $view;
    }

    /**
     * Return the Identity of the current authenticated user
     *
     * @return Identity
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Return the User data of the current authenticated user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Check there is a user authenticated.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated($allowRedirect = true)
    {
        if (!$this->identity instanceof Identity) {
            if ($allowRedirect) {
                $preAuthRequest = new Container('PreAuthRequest');

                $preAuthRequest->url = (string)$this->getRequest()->getUri();
            }

            //---

            // Redirect to the About You page
            return $this->redirect()->toRoute('login', [
                'state' => 'timeout'
            ]);
        }

        return true;
    }

    /**
     * delete cloned data for this seed id from session container if it exists.
     * to make sure clone data will be loaded freshly when actor form is rendered.
     *
     * @param int $seedId
     */
    protected function resetSessionCloneData($seedId)
    {
        $cloneContainer = new Container('clone');

        if ($cloneContainer->offsetExists($seedId)) {
            unset($cloneContainer->$seedId);
        }
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
}
