<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\User\Details;

class VerifyEmailAddressController extends AbstractBaseController
{
    /**
     * @var Details
     */
    private $aboutYouDetails;

    public function verifyAction()
    {
        //---------------------------

        // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
        $session = $this->getSessionManager();
        $session->getStorage()->clear();
        $session->initialise();

        //---------------------------

        $token = $this->params()->fromRoute('token');
        
        if ($this->aboutYouDetails->updateEmailUsingToken( $token ) === true) {

            $this->flashMessenger()
                ->addSuccessMessage('Your email address was successfully updated. Please login with your new address.');
            
        } else {
            $this->flashMessenger()
                ->addErrorMessage('There was an error updating your email address');
        }

        // will either go to the login page or the dashboard
        return $this->redirect()->toRoute( 'login' );
    }

    public function setAboutYouDetails(Details $aboutYouDetails)
    {
        $this->aboutYouDetails = $aboutYouDetails;
    }
}