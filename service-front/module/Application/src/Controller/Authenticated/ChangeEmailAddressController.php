<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class ChangeEmailAddressController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    public function indexAction()
    {
        $form = $this->getFormElementManager()->get('Application\Form\User\ChangeEmailAddress');
        $form->setAttribute('action', $this->url()->fromRoute('user/change-email-address'));

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
                //  Get the user ID and email address
                $newEmailAddress = $form->getData()['email'];

                $userService = $this->getUserService();
                $result = $userService->requestEmailUpdate($newEmailAddress, $currentEmailAddress);

                if ($result === true) {
                    return (new ViewModel([
                        'email' => $newEmailAddress
                    ]))->setTemplate('application/authenticated/change-email-address/email-sent.twig');
                } else {
                    $error = $result;
                }
            }
        }

        $cancelUrl = '/user/about-you';
        $context = compact('form', 'error', 'currentEmailAddress', 'cancelUrl');
        return new ViewModel($context);
    }
}
