<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class AuthController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

        // Ensure the user it logged out
        $authenticationService->clearIdentity();

        //-----------------------

        // This action can be called with a number of combinations of messages.
        # TODO - how to deal with these?

        switch( $this->params('state') ){
            case 'pwrest':
                // The user has just reset their password
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

    /**
     * Logs the user out by clearing the identity from the session.
     *
     * @return \Zend\Http\Response
     */
    public function logoutAction(){

        $this->getServiceLocator()->get('AuthenticationService')->clearIdentity();

        $this->getServiceLocator()->get('SessionManager')->regenerateId(true);

        //---

        return $this->redirect()->toUrl( $this->config()['redirects']['logout'] );

    }

} // class
