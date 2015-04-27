<?php

namespace Application\Controller\General;

use Application\Form\User\Login as LoginForm;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Zend\Session\Container;

class AuthController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->preventAuthenticatedUser();
        if( $check !== true ){ return $check; }

        //---

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        // Create an instance of the login form.
        $form = $this->getLoginForm();

        //-----------------------

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

        //---

        $authError = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            // If the form is valid...
            if ($form->isValid()) {

                // Check if we're going to redirect to a deep(er) link (before we kill the session)
                $preAuthRequest = new Container('PreAuthRequest');

                if( $preAuthRequest->url ){
                    $nextUrl = $preAuthRequest->url;
                }

                //---

                // Ensure no user is logged in and ALL session data is cleared then re-initialise it.

                $session = $this->getServiceLocator()->get('SessionManager');

                $session->getStorage()->clear();
                $session->initialise();

                //---

                $email = $form->getData()['email'];
                $password = $form->getData()['password'];

                $authenticationAdapter = $this->getServiceLocator()->get('AuthenticationAdapter');

                // Pass the user's email address and password...
                $authenticationAdapter->setEmail( $email )->setPassword( $password );

                // Perform the authentication..
                $result = $authenticationService->authenticate( $authenticationAdapter );


                // If all went well...
                if( $result->isValid() ){

                    // Regenerate the session ID post authentication
                    $session->regenerateId(true);

                    //---

                    if( isset($nextUrl) ){
                        return $this->redirect()->toUrl( $nextUrl );
                    }

                    // Else Send them to the dashboard...
                    return $this->redirect()->toRoute( 'user/dashboard' );

                } // if

                //---

                // else authentication failed...


                // Create a new instance of the login form as we'll need a new Csrf token.
                $form = $this->getLoginForm();

                // Keep the entered email address.
                $form->setData( [ 'email' => $email ] );

                //---

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

            } // if form is valid

        } // if is post

        //---

        $isTimeout = ( $this->params('state') == 'timeout' );

        //---

        return new ViewModel( [ 'form'=>$form, 'authError'=>$authError, 'pageTitle' => 'Sign in', 'isTimeout'=>$isTimeout ] );

    } // function

    /**
     * Returns a new instance of the Login form.
     *
     * @return LoginForm
     */
    private function getLoginForm(){

        $form = new LoginForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('login') );

        return $form;

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
