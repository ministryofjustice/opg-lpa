<?php

namespace Application\Controller\General;

use DateTime;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class AuthController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        $session = $this->getServiceLocator()->get('SessionManager');

        // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
        $session->getStorage()->clear();
        $session->initialise();

        //---

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

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
            $authenticationAdapter->setEmail( $email )->setPassword( $password );

            // Perform the authentication..
            $result = $authenticationService->authenticate( $authenticationAdapter );


            // If all went well...
            if( $result->isValid() ){

                // Regenerate the session ID post authentication
                $session->regenerateId(true);

                // Send them to the dashboard...
                return $this->redirect()->toRoute( 'user/dashboard' );

            } // if

            //---

            // else authentication failed...

            $message = $result->getMessages();

            // If there is a message, extract it (there will only ever be one).
            if( is_array($message) && count($message) > 0 ){
                $message = array_pop($message);
            }

            switch( $message ){
                case 'not-activated':
                    $authError = 'Your account has not yet been activated.';
                    break;
                case 'authentication-failed':
                default:
                    $authError = 'Email and password combination not recognised.';
            }


            // Help mitigate brute force attacks.
            sleep(1);

        } // if

        //---

        return new ViewModel( [ 'error'=>$authError, 'pageTitle' => 'Sign in' ] );

    } // function


    /**
     * Logs the user out by clearing the identity from the session.
     *
     * @return \Zend\Http\Response
     */
    public function logoutAction(){

        $this->getServiceLocator()->get('AuthenticationService')->clearIdentity();
        $this->getServiceLocator()->get('SessionManager')->destroy([ 'clear_storage'=>true ]);

        //---

        return $this->redirect()->toUrl( $this->config()['redirects']['logout'] );

    } // function

} // class
