<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Zend\View\Model\ViewModel;

class ChangeEmailAddressController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\User\ChangeEmailAddress');
        $form->setAttribute('action', $this->url()->fromRoute('user/change-email-address'));

        $error = null;

        // This form needs to check the user's current password, thus we pass it the Authentication Service
        $authentication = $this->getAuthenticationService();

        $currentAddress = (string)$this->getUser()->email;
        $authentication->setEmail($currentAddress);

        $form->setAuthenticationService($authentication);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                //  Get the user ID and email address
                $userId = $this->getIdentity()->id();
                $email = $form->getData()['email'];

                $userService = $this->getUserService();
                $result = $userService->requestEmailUpdate($userId, $email, $currentAddress);

                if ($result === true) {
                    return (new ViewModel([
                        'email' => $email
                    ]))->setTemplate('application/authenticated/change-email-address/email-sent.twig');
                } else {
                    $error = $result;
                }
            }
        }

        return new ViewModel(compact('form', 'error', 'currentAddress'));
    }
}
