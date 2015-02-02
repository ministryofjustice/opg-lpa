<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class AuthController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        // This action can be called with a number of combinations of messages.
        # TODO - how to deal with these?

        switch( $this->params('state') ){
            case 'timeout':
                // The user has timed out
            case 'worldpay':
                // The user needs to log back in to complete their payment
        }

        //-----------------------

        $authError = null;

        $email = $this->params()->fromPost('email');
        $password = $this->params()->fromPost('password');

        if( !empty($email) && !empty($password) ){

            $authenticationService = $this->getServiceLocator()->get('AuthenticationService');
            $authenticationAdapter = $this->getServiceLocator()->get('AuthenticationAdapter');

            // Pass the user's email address and password...
            $authenticationAdapter->setCredentials( $email, $password );

            // Perform the authentication..
            $result = $authenticationService->authenticate( $authenticationAdapter );

            // If all went well...
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

    public function logoutAction(){

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

        $authenticationService->clearIdentity();

        //---

        // Regenerate the session ID post logout
        $this->getServiceLocator()->get('SessionManager')->regenerateId(true);

        //---

        return $this->redirect()->toUrl( $this->config()['redirects']['logout'] );

    }

} // class
