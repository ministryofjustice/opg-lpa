<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;

class VerifyEmailAddressController extends AbstractBaseController {
  
    public function verifyAction()
    {
        $userId = $this->params()->fromRoute('userId');
        $token = $this->params()->fromRoute('token');
        
        $service = $this->getServiceLocator()->get('AboutYouDetails');
        
        if ($service->updateEmailUsingToken( $userId, $token ) === true) {
            $message = 'Your email address was succesfully updated. Please login with your new address.';
            
            $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
            
            if( isset($detailsContainer->user) ) {
                unset($detailsContainer->user);
            }
            
        } else {
            $message = 'There was an error updating your email address';
        }
        
        $this->flashMessenger()->addErrorMessage($message);
        // will either go to the login page or the dashboard
        return $this->redirect()->toRoute( 'login' );
    }
}