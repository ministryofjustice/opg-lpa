<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class ChangePasswordController extends AbstractAuthenticatedController
{
    use LoggerTrait;

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

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $currentPassword = $data['password_current'];
                $newPassword = $data['password'];

                $userService = $this->getUserService();
                $result = $userService->updatePassword($currentPassword, $newPassword);

                if ($result === true) {
                    /**
                     * psalm doesn't understand Laminas MVC plugins
                     * @psalm-suppress UndefinedMagicMethod
                     */
                    $this->flashMessenger()->addSuccessMessage(
                        'Your new password has been saved. ' .
                        'Please remember to use this new password to sign in from now on.'
                    );
                    return $this->redirectToRoute('user/about-you');
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
