<?php
namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class RegisterController extends AbstractBaseController {

    protected $contentHeader = 'blank-header-partial.phtml';

    /**
     * Register a new account.
     *
     * @return ViewModel
     */
    public function indexAction(){

        // gov.uk is not allowed to point users directly at this page.
        if( $this->getRequest()->getHeader('Referer') != false ){
            if( $this->getRequest()->getHeader('Referer')->uri()->getHost() === 'www.gov.uk' ){
                return $this->redirect()->toRoute('home');
            }
        }

        //---

        $check = $this->preventAuthenticatedUser();
        
        if( $check !== true ) {
            
            $this->log()->info(
                'Authenticated user attempted to access registration page',
                $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
            );
            
            return $check; 
        }

        //---

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\Registration');
        $form->setAttribute( 'action', $this->url()->fromRoute('register') );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                // Create a callback for the Model to get the callback URL from.
                $callback = function( $token ) {
                    return $this->url()->fromRoute('register/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
                };

                $result = $this->getServiceLocator()->get('Register')->registerAccount(
                    $form->getData()['email'],
                    $form->getData()['password'],
                    $callback
                );

                if( $result === true ){

                    return (new ViewModel( ['email'=>$form->getData()['email']] ))->setTemplate('application/register/email-sent');

                }

                $error = $result;

            } // if

        } // if

        //---

        return new ViewModel( compact('form', 'error') );

    } // function

    /**
     * Confirm the email address, activating the account.
     *
     * @return ViewModel
     */
    public function confirmAction(){

        $token = $this->params()->fromRoute('token');

        if( empty($token) ){
            return new ViewModel( [ 'error'=>'invalid-token' ] );
        }

        //---

        // Ensure they're not logged in whilst activating a new account.
        $this->getServiceLocator()->get('AuthenticationService')->clearIdentity();

        $session = $this->getServiceLocator()->get('SessionManager');
        $session->getStorage()->clear();
        $session->initialise();

        //---

        /**
         * This returns:
         *      TRUE - If the user account exists. The account has been activated, or was already activated.
         *      FALSE - If the user account does not exist.
         *
         *  Alas no other details are returned.
         */
        $success = $this->getServiceLocator()->get('Register')->activateAccount( $token );

        if(isset($this->contentHeader)) {
            $this->layout()->contentHeader = $this->contentHeader;
        }

        if( !$success ){
            return new ViewModel( [ 'error'=>'account-missing' ] );
        }

        return new ViewModel();

    } // function

} // class
