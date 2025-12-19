<?php

namespace Application\Controller;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use MakeShared\DataModel\User\User;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\Session\Container;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use DateTime;
use MakeShared\Logging\LoggerTrait;

abstract class AbstractAuthenticatedController extends AbstractBaseController
{
    use LoggerTrait;

    /**
     * Identity of the logged in user
     */
    private ?Identity $identity = null;

    /**
     * User details of the logged in user
     */
    private ?User $user = null;

    /**
     * Flag to indicate if complete user details are required when accessing this controller
     * Override in descendant if required
     */
    protected bool $requireCompleteUserDetails = true;

    public function __construct(
        protected AbstractPluginManager $formElementManager,
        protected SessionManagerSupport $sessionManagerSupport,
        protected AuthenticationService $authenticationService,
        protected array $config,
        protected Container $userDetailsSession,
        protected LpaApplicationService $lpaApplicationService,
        protected UserService $userService,
        protected SessionUtility $sessionUtility,
    ) {
        parent::__construct($formElementManager, $sessionManagerSupport, $authenticationService, $config, $sessionUtility);

        //  If there is a user identity set up the user - if this is missing the request
        // will be bounced in the onDispatch function
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
     * @return bool|mixed|\Laminas\Http\Response
     * @throws \Exception
     */
    public function onDispatch(MvcEvent $e)
    {
        // Before the user can access any actions that extend this controller...
        // Check we have a user set, thus ensuring an authenticated user.
        if (($authenticated = $this->checkAuthenticated()) !== true) {
            return $authenticated;
        }

        $this->getLogger()->info('Request to ' . get_class($this), [
            'userId' => $this->identity->id(),
        ]);

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
            // If seems there is a user associated with the session but it is not well formed
            // Therefore destroy the session and logout the user
            $this->getAuthenticationService()->clearIdentity();
            $this->getSessionManager()->destroy([
                'clear_storage' => true
            ]);

            return $this->redirect()->toRoute('login', [
                'state' => 'timeout'
            ]);
        }

        // Inject the user into the view parameters
        $view = parent::onDispatch($e);

        if ($view instanceof ViewModel && !$view instanceof JsonModel) {
            $view->setVariable('signedInUser', $this->user);
            $view->setVariable(
                'secondsUntilSessionExpires',
                $this->identity->tokenExpiresAt()->getTimestamp() - new DateTime()->getTimestamp()
            );
        }

        return $view;
    }

    /**
     * Return the Identity of the current authenticated user
     */
    public function getIdentity(): ?Identity
    {
        return $this->identity;
    }

    /**
     * Return the User data of the current authenticated user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Check there is a user authenticated.
     *
     * @return bool|\Laminas\Http\Response
     */
    protected function checkAuthenticated($allowRedirect = true)
    {
        if (!$this->identity instanceof Identity) {
            if ($allowRedirect) {
                $this->sessionUtility->setInMvc(
                    'PreAuthRequest',
                    'url',
                    (string) $this->convertRequest()->getUri()
                );
            }

            // If the user's identity was cleared because of a genuine timeout,
            // redirect to the login page with session timeout; otherwise,
            // redirect to the login page and show the "service unavailable" message.
            $authFailureCode = $this->sessionUtility->getFromMvc('AuthFailureReason', 'code');
            if (is_null($authFailureCode)) {
                return $this->redirect()->toRoute('login', [
                    'state' => 'timeout'
                ]);
            }

            return $this->redirect()->toRoute('login', [
                'state' => 'internalSystemError'
            ]);
        }

        return true;
    }

    /**
     * delete cloned data for this seed id from session container if it exists.
     * to make sure clone data will be loaded freshly when actor form is rendered.
     */
    protected function resetSessionCloneData(string $seedId): void
    {
        $this->sessionUtility->unsetInMvc('clone', $seedId);
    }

    protected function getLpaApplicationService(): LpaApplicationService
    {
        return $this->lpaApplicationService;
    }

    protected function getUserService(): UserService
    {
        return $this->userService;
    }
}
