<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

class ChangeEmailAddressController extends AbstractAuthenticatedController {

    public function indexAction()
    {
        $currentAddress = (string)$this->getUserDetails()->email;
        $userId = (string)$this->getUserDetails()->id;

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

                $emailConfirmCallback = function( $userId, $token ) {
                    return $this->url()->fromRoute('user/change-email-address/verify', [
                            'token'=>$token,
                        ], 
                        [ 'force_canonical' => true ] 
                    );
                };
                
                $result = $service->requestEmailUpdate( $form, $emailConfirmCallback, $currentAddress, $userId );
                
                //---

                if( $result === true ){

                    return (new ViewModel( ['email'=>$form->getData()['email']] ))->setTemplate('application/change-email-address/email-sent');

                } else {
                    $error = $result;
                }

            }

        }

        //----------------------

        return new ViewModel( compact( 'form', 'error', 'currentAddress' ) );
    }
}