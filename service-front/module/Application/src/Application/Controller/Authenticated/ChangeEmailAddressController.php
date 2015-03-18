<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Application\Form\User\ChangeEmailAddress as ChangeEmailAddressForm;

class ChangeEmailAddressController extends AbstractAuthenticatedController {

    public function indexAction()
    {

        $currentAddress = (string)$this->getUserDetails()->email;

        //----------------------


        $form = new ChangeEmailAddressForm();


        $form->setAttribute( 'action', $this->url()->fromRoute('user/change-email-address') );

        $error = null;

        //----------------------

        // This form needs to check the user's current password,
        // thus we pass it the Authentication Service

        $authentication =   $this->getServiceLocator()->get('AuthenticationService');
        $adapter =          $this->getServiceLocator()->get('AuthenticationAdapter');

        // Pass the user's current email address...
        $adapter->setEmail( $currentAddress );

        $authentication->setAdapter( $adapter );

        $form->setAuthenticationService( $authentication );

        //----------------------

        $request = $this->getRequest();

        if ($request->isPost()) {

            //---

            $form->setData($request->getPost());

            //---

            if ($form->isValid()) {

                $service = $this->getServiceLocator()->get('AboutYouDetails');

                $result = $service->updatePassword( $form );

                // Clear the old details out the session.
                // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
                unset($detailsContainer->user);

                //---

                if( $result === true ){

                    $this->flashMessenger()->addSuccessMessage('Your new email address has been saved. Please remember to use this new email address to sign in from now on.');

                    return $this->redirect()->toRoute( 'user/about-you' );

                }

            }

        }

        //----------------------

        $currentAddress = (string)$this->getUserDetails()->email;

        $pageTitle = 'Change your sign-in email address';

        return new ViewModel( compact( 'form', 'error', 'pageTitle', 'currentAddress' ) );
    }

}
