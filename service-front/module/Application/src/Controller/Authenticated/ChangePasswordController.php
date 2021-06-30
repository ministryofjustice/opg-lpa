<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Laminas\View\Model\ViewModel;

class ChangePasswordController extends AbstractAuthenticatedController
{
    /**
     * @return ViewModel|\Laminas\Http\Response
     */
    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\User\ChangePassword');
        $form->setAttribute('action', $this->url()->fromRoute('user/change-password'));

        $error = null;

        // This form needs to check the user's current password, thus we pass it the Authentication Service
        $authentication = $this->getAuthenticationService();

        $currentEmailAddress = (string)$this->getUser()->email;
        $authentication->setEmail($currentEmailAddress);

        $form->setAuthenticationService($authentication);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $currentPassword = $data['password_current'];
                $newPassword = $data['password'];

                $userService = $this->getUserService();
                $result = $userService->updatePassword($currentPassword, $newPassword);

                if ($result === true) {
                    $this->flashMessenger()->addSuccessMessage('Your new password has been saved. Please remember to use this new password to sign in from now on.');

                    return $this->redirect()->toRoute('user/about-you');
                } else {
                    $error = $result;
                }
            }
        }

        $pageTitle = 'Change your password';
        $cancelUrl = '/user/about-you';
        return new ViewModel(compact('form', 'error', 'pageTitle', 'cancelUrl'));
    }
}
