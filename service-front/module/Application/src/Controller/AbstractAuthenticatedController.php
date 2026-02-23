<?php

namespace Application\Controller;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use MakeShared\DataModel\User\User;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\AbstractPluginManager;
use MakeShared\Logging\LoggerTrait;

abstract class AbstractAuthenticatedController extends AbstractBaseController
{
    use LoggerTrait;

    /**
     * Identity of the logged-in user
     */
    private ?Identity $identity = null;

    /**
     * User details of the logged-in user
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
        protected LpaApplicationService $lpaApplicationService,
        protected UserService $userService,
        protected SessionUtility $sessionUtility,
    ) {
        parent::__construct($formElementManager, $sessionManagerSupport, $authenticationService, $config, $sessionUtility);

        // If there is a user identity set up the user - if this is missing the request
        // will be bounced in the onDispatch function
        if ($authenticationService->hasIdentity()) {
            $this->identity = $authenticationService->getIdentity();
            $this->user = $this->sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'user');
        }
    }

    /**
     * Do some pre-dispatch checks...
     *
     * @return bool|mixed|\Laminas\Http\Response
     * @throws \Exception
     */
    public function onDispatch(MvcEvent $e)
    {
        $this->getLogger()->info('Request to ' . get_class($this), [
            'userId' => $this->identity->id(),
        ]);

        return parent::onDispatch($e);
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
