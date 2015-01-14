<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class LoginController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        $authError = null;

        $email = $this->params()->fromPost('email');
        $password = $this->params()->fromPost('password');

        if( !empty($email) && !empty($password) ){

            $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

            $authenticationAdapter = $this->getServiceLocator()->get('LpaApiClientAuthAdapter');

            $authenticationAdapter->setCredentials( $email, $password );

            $result = $authenticationService->authenticate( $authenticationAdapter );

            if( $result->isValid() ){

                // Regenerate the session ID post authentication
                $this->getServiceLocator()->get('SessionManager')->regenerateId(true);

                // Send them to the dashboard...
                return $this->redirect()->toRoute( 'user/dashboard' );
            }

            // Else
            $authError = 'Email and password combination not recognised.';

            // Help mitigate brute force attacks.
            sleep(1);

        } // if

        //---

        return new ViewModel( [ 'error'=>$authError ] );

    } // function

} // class
