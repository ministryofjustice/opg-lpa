<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

use Application\Form\User\ResetPasswordEmail as ResetPasswordEmailForm;

class ForgotPasswordController extends AbstractActionController
{

    /**
     * GET: Display's the 'Enter your email address' form.
     * POST: Sends the password reset email.
     *
     * @return ViewModel
     */
    public function indexAction(){

        $form = new ResetPasswordEmailForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('forgot-password') );

        $sent = false;
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
                    $sent = true;
                } else {
                    $error = $result;
                }

            } // if

        } // if

        return new ViewModel( compact('form', 'error', 'sent') );

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
