<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

use Application\Controller\AbstractBaseController;

class ForgotPasswordController extends AbstractBaseController
{

    /**
     * GET: Display's the 'Enter your email address' form.
     * POST: Sends the password reset email.
     *
     * @return ViewModel
     */
    public function indexAction(){

        $check = $this->preventAuthenticatedUser();
        if( $check !== true ){ return $check; }

        //---

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\ResetPasswordEmail');
        $form->setAttribute( 'action', $this->url()->fromRoute('forgot-password') );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                // Create a callback for the Model to get the forgotten password route.
                $fpCallback = function( $token ) {
                    return $this->url()->fromRoute('forgot-password/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
                };

                // Create a callback for the Model to get the activate callback.
                $activateCallback = function( $token ) {
                    return $this->url()->fromRoute('register/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
                };

                //---

                $result = $this->getServiceLocator()->get('PasswordReset')->requestPasswordResetEmail( $form->getData()['email'], $fpCallback, $activateCallback );

                if( $result === true || $result == 'account-not-activated' ) {
                    
                      $viewParams = [
                          'email' => $form->getData()['email'],
                          'accountNotActivated' => ($result === 'account-not-activated'),
                      ];
                      
                      return (new ViewModel( $viewParams ))->setTemplate('application/forgot-password/email-sent');

                }

                $error = $result;

            } // if

        } // if

        return new ViewModel(
            array_merge([
                    'pageTitle' => 'Reset your password',    
                ],
                compact('form', 'error')
            ) 
        );

    } // function

    /**
     * GET: Displays the 'Enter new password' form.
     * POST: Sets the new password.
     *
     * @return ViewModel
     */
    public function resetPasswordAction(){

        $token = $this->params()->fromRoute('token');

        if( empty($token) ){
            return (new ViewModel())->setTemplate('application/forgot-password/invalid-reset-token');
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\SetPassword');
        $form->setAttribute( 'action', $this->url()->fromRoute('forgot-password/callback', [ 'token'=>$token ] ) );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {
                
                $result = $this->getServiceLocator()->get('PasswordReset')->setNewPassword( $token, $form->getData()['password'] );
                
                // if all good, direct them back to login.
                if( $result === true ){

                    $this->flashMessenger()->addSuccessMessage('Password successfully reset');

                    // Send them to login...
                    return $this->redirect()->toRoute( 'login' );

                }

                // else there was an error
                $error = $result;

            } // if

        } // if

        //---------------------------

        // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
        $session = $this->getServiceLocator()->get('SessionManager');
        $session->getStorage()->clear();
        $session->initialise();

        //---------------------------

        return new ViewModel(
            array_merge([
                    'pageTitle' => 'Reset your password',    
                ],
                compact('form', 'error')
            ) 
        );
    }
}
