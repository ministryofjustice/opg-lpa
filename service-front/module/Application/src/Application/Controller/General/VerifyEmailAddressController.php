<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;

class VerifyEmailAddressController extends AbstractBaseController {
  
    public function verifyAction()
    {
        //---------------------------

        // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
        $session = $this->getServiceLocator()->get('SessionManager');
        $session->getStorage()->clear();
        $session->initialise();

        //---------------------------

        $token = $this->params()->fromRoute('token');
        
        $service = $this->getServiceLocator()->get('AboutYouDetails');
        
        if ($service->updateEmailUsingToken( $token ) === true) {

            $this->flashMessenger()
                ->addSuccessMessage('Your email address was successfully updated. Please login with your new address.');
            
        } else {
            $this->flashMessenger()
                ->addErrorMessage('There was an error updating your email address');
        }

        // will either go to the login page or the dashboard
        return $this->redirect()->toRoute( 'login' );
    }
}