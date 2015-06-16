<?php
namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class RegisterController extends AbstractBaseController {

    protected $contentHeader = 'confirm-partial.phtml';

    /**
     * Register a new account.
     *
     * @return ViewModel
     */
    public function indexAction(){

        $check = $this->preventAuthenticatedUser();
        if( $check !== true ){ return $check; }

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
