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

                $result = $service->updateEmailAddress( $form );

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

                    $this->flashMessenger()->addSuccessMessage('Your new email address has been saved. Please remember to use this new email address to sign in from now on.');

                    return $this->redirect()->toRoute( 'user/about-you' );

                } else {
                    $error = $result;
                }

            }

        }

        //----------------------

        $pageTitle = 'Change your sign-in email address';

        return new ViewModel( compact( 'form', 'error', 'pageTitle', 'currentAddress' ) );
    }

}
