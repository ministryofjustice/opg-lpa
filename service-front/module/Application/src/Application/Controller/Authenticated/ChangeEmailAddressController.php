<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class ChangeEmailAddressController extends AbstractAuthenticatedController {

    public function indexAction()
    {

        $currentAddress = (string)$this->getUserDetails()->email;

        //----------------------

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\ChangeEmailAddress');
        $form->setAttribute( 'action', $this->url()->fromRoute('user/change-email-address') );

        $error = null;

        //----------------------

        // This form needs to check the user's current password,
        // thus we pass it the Authentication Service

        $authentication =   $this->getServiceLocator()->get('AuthenticationService');
        $adapter =          $this->getServiceLocator()->get('AuthenticationAdapter');

        // Pass the user's current email address...
        $adapter->setEmail( $currentAddress );

        $authentication->setAdapter( $adapter );

        $form->setAuthenticationService( $authentication );

        //----------------------

        $request = $this->getRequest();

        if ($request->isPost()) {

            //---

            $form->setData($request->getPost());

            //---

            if ($form->isValid()) {

                $service = $this->getServiceLocator()->get('AboutYouDetails');

                $emailConfirmCallback = function( $token ) {
                    return $this->url()->fromRoute('user/change-email-address/callback', [ 'token'=>$token ], [ 'force_canonical' => true ] );
                };
                
                $result = $service->requestEmailUpdate( $form, $emailConfirmCallback, $currentAddress );
                
                //---

                if( $result === true ){
                    
                    /**
                     * When removing v1, the whole if statement below can be deleted.
                     *
                     * #v1Code
                     */
                    if( $this->getServiceLocator()->has('ChangeEmailAddress') ){

                        // Update Email Address on Account Service
                        $this->getServiceLocator()->get('ChangeEmailAddress')->changeAddress(
                            $currentAddress,
                            $form->getDataForModel()['email']
                        );

                    } // if

                    // end #v1Code


                    //---

                    // Clear the old details out the session.
                    // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                    $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
                    unset($detailsContainer->user);

                    return (new ViewModel( ['email'=>$form->getData()['email']] ))->setTemplate('application/change-email-address/email-sent');

                } else {
                    $error = $result;
                }

            }

        }

        //----------------------

        $pageTitle = 'Change your sign-in email address';

        return new ViewModel( compact( 'form', 'error', 'pageTitle', 'currentAddress' ) );
    }

    public function verifyAction()
    {
        $token = $this->params()->fromRoute('token');
        
        $service = $this->getServiceLocator()->get('AboutYouDetails');
        
        if ($service->updateEmailUsingToken( $token ) === true) {
            $message = 'Your email address was succesfully updated';
        } else {
            $message = 'There was an error updating your email address';
        }
        
        $this->flashMessenger()->addErrorMessage($message);
        return $this->redirect()->toRoute( 'user/about-you' );

    }
}
