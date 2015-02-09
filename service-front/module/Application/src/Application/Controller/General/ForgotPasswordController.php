<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ForgotPasswordController extends AbstractActionController
{

    /**
     * GET: Display's the 'Enter your email address' form.
     * POST: Sends the password reset email.
     *
     * @return ViewModel
     */
    public function indexAction(){

        $email = $this->params()->fromPost('email');
        $confirmation = $this->params()->fromPost('email_confirm');

        if( !empty($email) && !empty($confirmation) ){

            # TODO - checks addresses are the same.

            // Create a callback for the Model to get the callback URL from.
            $callback = function( $token ) {
                return $this->url()->fromRoute('forgot-password/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
            };

            $result = $this->getServiceLocator()->get('PasswordReset')->requestPasswordResetEmail( $email, $callback );

        } // if

        return new ViewModel();

    } // function

    /**
     * GET: Displays the 'Enter new password' form.
     * POST: Sets the new password.
     *
     * @return ViewModel
     */
    public function resetPasswordAction(){

        $token = $this->params()->fromRoute('token');

        if( !empty($token) ){

        }

        // Check the token is valid...
        $valid = $this->getServiceLocator()->get('PasswordReset')->isResetTokenValid( $token );

        //---------------------------

        $password = $this->params()->fromPost('password');
        $confirmation = $this->params()->fromPost('password_confirm');

        if( !empty($password) || !empty($confirmation) ){

            // Validate the password. # TODO

            //---

            $result = $this->getServiceLocator()->get('PasswordReset')->setNewPassword( $token, $password );

            // if all good, direct them back to login.
            if( $result == true ){

                $this->flashMessenger()->addSuccessMessage('Password successfully reset');

                // Send them to login...
                return $this->redirect()->toRoute( 'login' );

            }

        } // if

        //---------------------------

        var_dump($token, $valid); exit();

        return new ViewModel();
    }
}
