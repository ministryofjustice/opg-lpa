<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Flash\Flash;
use Application\Model\Service\User\Details as UserService;

class VerifyEmailAddressController extends AbstractBaseController
{
    /**
     * @var UserService
     *
     * psalm doesn't understand laminas-mvc plugins, so thinks the flashMessages()
     * method doesn't exist on this class; hence...
     * @psalm-suppress UndefinedMagicMethod
     */
    private $userService;

    public function verifyAction()
    {
        //---------------------------

        // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
        $session = $this->getSessionManager();
        $session->getStorage()->clear();
        $this->sessionManagerSupport->initialise();

        //---------------------------

        $token = $this->params()->fromRoute('token');

        if ($this->userService->updateEmailUsingToken($token) === true) {
            /** @psalm-suppress UndefinedMagicMethod */
            $this->flashMessages()
                ->flash(Flash::NAMESPACE_SUCCESS, 'Your email address was successfully updated. Please login with your new address.');
        } else {
            /** @psalm-suppress UndefinedMagicMethod */
            $this->flashMessages()
                ->flash(Flash::NAMESPACE_ERROR, 'There was an error updating your email address');
        }

        // will either go to the login page or the dashboard
        return $this->redirect()->toRoute('login');
    }

    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }
}
