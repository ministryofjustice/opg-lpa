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

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

        //---

        $authError = null;

        $email = $this->params()->fromPost('email');
        $password = $this->params()->fromPost('password');

        if( !empty($email) && !empty($password) ){

            // Ensure no user is logged in and ALL session data is cleared then re-initialise it.

            $session = $this->getServiceLocator()->get('SessionManager');

            $session->getStorage()->clear();
            $session->initialise();

            //---

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

        $isTimeout = ( $this->params('state') == 'timeout' );

        //---

        return new ViewModel( [ 'error'=>$authError, 'pageTitle' => 'Sign in', 'isTimeout'=>$isTimeout ] );

    } // function


    /**
     * Logs the user out by clearing the identity from the session.
     *
     * @return \Zend\Http\Response
     */
    public function logoutAction(){

        $this->clearSession();

        return $this->redirect()->toUrl( $this->config()['redirects']['logout'] );

    } // function


    /**
     * Wipes all session details post-account deletion.
     *
     * @return ViewModel
     */
    public function deletedAction(){

        $this->clearSession();

        return new ViewModel();

    } // function


    /**
     * Destroys the current session.
     */
    private function clearSession(){

        $this->getServiceLocator()->get('AuthenticationService')->clearIdentity();
        $this->getServiceLocator()->get('SessionManager')->destroy([ 'clear_storage'=>true ]);

    } // function

} // class
