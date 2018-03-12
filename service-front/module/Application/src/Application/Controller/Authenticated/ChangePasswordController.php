<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Zend\View\Model\ViewModel;

class ChangePasswordController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\User\ChangePassword');
        $form->setAttribute('action', $this->url()->fromRoute('user/change-password'));

        $error = null;

        // This form needs to check the user's current password, thus we pass it the Authentication Service
        $authentication =   $this->getAuthenticationService();

        $currentAddress = (string)$this->getUser()->email;
        $authentication->setEmail($currentAddress);

        $form->setAuthenticationService($authentication);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $currentPassword = $data['password_current'];
                $newPassword = $data['password'];

                $userService = $this->getUserService();
                $result = $userService->updatePassword($this->getIdentity()->id(), $currentPassword, $newPassword);

                if ($result === true) {
                    $this->flashMessenger()->addSuccessMessage('Your new password has been saved. Please remember to use this new password to sign in from now on.');

                    return $this->redirect()->toRoute('user/about-you');
                } else {
                    $error = $result;
                }
            }
        }

        $pageTitle = 'Change your password';

        return new ViewModel(compact('form', 'error', 'pageTitle'));
    }
}
