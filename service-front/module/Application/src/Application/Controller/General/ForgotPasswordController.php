<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

use Application\Form\User\ResetPasswordEmail as ResetPasswordEmailForm;
use Application\Form\User\SetPassword as ResetPasswordPasswordForm;
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
        
        $form = new ResetPasswordEmailForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('forgot-password') );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                // Create a callback for the Model to get the callback URL from.
                $callback = function( $token ) {
                    return $this->url()->fromRoute('forgot-password/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
                };

                $result = $this->getServiceLocator()->get('PasswordReset')->requestPasswordResetEmail( $form->getData()['email'], $callback );

                if( $result === true ){

                    return (new ViewModel( ['email'=>$form->getData()['email']] ))->setTemplate('application/forgot-password/email-sent');

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

        // Check the token is valid...
        $valid = $this->getServiceLocator()->get('PasswordReset')->isResetTokenValid( $token );

        if( !$valid ){
            return (new ViewModel())->setTemplate('application/forgot-password/invalid-reset-token');
        }

        //-------------------------------------
        // We have a valid reset token...

        $form = new ResetPasswordPasswordForm();
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

        return new ViewModel(
            array_merge([
                    'pageTitle' => 'Reset your password',    
                ],
                compact('form', 'error')
            ) 
        );
    }
}
