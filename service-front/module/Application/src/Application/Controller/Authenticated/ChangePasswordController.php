<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class ChangePasswordController extends AbstractAuthenticatedController
{
    public function indexAction(){

        $currentAddress = (string)$this->getUserDetails()->email;

        //----------------------

        $form = $this->getFormElementManager()->get('Application\Form\User\ChangePassword');
        $form->setAttribute( 'action', $this->url()->fromRoute('user/change-password') );

        $error = null;

        //----------------------

        // This form needs to check the user's current password,
        // thus we pass it the Authentication Service

        $authentication =   $this->getAuthenticationService();
        $adapter =          $this->getAuthenticationAdapter();

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

                $service = $this->getAboutYouDetails();

                $result = $service->updatePassword( $form );

                //---

                if( $result === true ){

                    $this->flashMessenger()->addSuccessMessage('Your new password has been saved. Please remember to use this new password to sign in from now on.');

                    return $this->redirect()->toRoute( 'user/about-you' );

                } else {
                    $error = $result;
                }

            } // if

        } // if

        //----------------------

        $pageTitle = 'Change your password';

        return new ViewModel( compact( 'form', 'error', 'pageTitle' ) );

    }
}
