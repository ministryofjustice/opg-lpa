<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Application\Model\Service\ServiceDataInputInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;
use Opg\Lpa\Api\Client\Client;

class Details implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function load(){

        $client = $this->getServiceLocator()->get('ApiClient');

        return $client->getAboutMe();

    }

    /**
     * Update the user's basic details.
     *
     * @param ServiceDataInputInterface $details
     * @return mixed
     */
    public function updateAllDetails( ServiceDataInputInterface $details ){

        $this->getServiceLocator()->get('Logger')->info(
            'Updating user details',
            $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        //---

        // Load the existing details...
        $userDetails = $client->getAboutMe();

        // Apply the new ones...
        $userDetails->populateWithFlatArray( $details->getDataForModel() );

        //---

        $validator = $userDetails->validate();

        if( $validator->hasErrors() ){
            throw new \RuntimeException('Unable to save details');
        }

        //---

        $result = $client->setAboutMe( $userDetails );

        if( $result !== true ){
            throw new \RuntimeException('Unable to save details');
        }

        return $userDetails;

    } // function

    /**
     * Update the user's email address.
     *
     * @param ServiceDataInputInterface $details
     * @return bool|string
     */
    public function requestEmailUpdate( ServiceDataInputInterface $details, $activateEmailCallback, $currentAddress ){
        
        $identityArray = $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray();
        
        $this->getServiceLocator()->get('Logger')->info(
            'Requesting email update to new email: ' . $details->getDataForModel()['email'],
            $identityArray    
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $updateToken = $client->requestEmailUpdate( strtolower($details->getDataForModel()['email']) );

        //---

        if( !is_string($updateToken) ){

            // Error...
            $body = $client->getLastContent();

            if( isset($body['detail']) ){
                switch ($body['detail']) {
                    case 'User already has this email' : 
                        return 'user-already-has-email';
                    case 'Email already exists for another user': 
                        return 'email-already-exists';
                    default: 
                        return 'unknown-error';
                }
            }

            return "unknown-error";
            
        } // if

        return $this->sendActivateNewEmailEmail( $details->getDataForModel()['email'], $activateEmailCallback( $updateToken ) );


    } // function
    
    function sendActivateNewEmailEmail( $newEmailAddress, $activateUrl ) {
        
        $this->getServiceLocator()->get('Logger')->info(
            'Sending new email verification email'
        );
        
        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
        
        $message->addTo( $newEmailAddress );
        
        $message->setSubject( 'Please verify your new email address' );
        
        //---
        
        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-newemail-verification');
        
        //---
        
        // Load the content from the view and merge in our variables...
        $viewModel = new \Zend\View\Model\ViewModel();
        $viewModel->setTemplate( 'email/new-email-verify.phtml' )->setVariables([
            'activateUrl' => $activateUrl,
        ]);
        
        $content = $this->getServiceLocator()->get('ViewRenderer')->render( $viewModel );
        
        //---
        
        $html = new MimePart( $content );
        $html->type = "text/html";
        
        $body = new MimeMessage();
        $body->setParts([$html]);
        
        $message->setBody($body);
        
        //--------------------
        
        try {
        
            $this->getServiceLocator()->get('MailTransport')->send($message);
        
        } catch ( \Exception $e ){
        
            return "failed-sending-email";
        
        }
        
        return true;
        
    }
    
    function updateEmailUsingToken( $emailUpdateToken ) {
        
        $this->getServiceLocator()->get('Logger')->info(
            'Updating email using token'
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');
        
        return $client->updateAuthEmail( $emailUpdateToken );
    }

    /**
     * Update the user's password.
     *
     * @param ServiceDataInputInterface $details
     * @return bool|string
     */
    public function updatePassword( ServiceDataInputInterface $details ){

        $identity = $this->getServiceLocator()->get('AuthenticationService')->getIdentity();
        
        $this->getServiceLocator()->get('Logger')->info(
            'Updating password',
            $identity->toArray()
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $result = $client->updateAuthPassword(
            $details->getDataForModel()['password_current'],
            $details->getDataForModel()['password'] 
        );

        //---

        if( !is_string($result) ){

            return 'unknown-error';

        } // if

        // Update the identity with the new token to avoid being
        // logged out after the redirect. We don't need to update the token
        // on the API client because this will happen on the next request
        // when it reads it from the identity.
        $identity->setToken($result);
        
        return true;

    } // function

} // class
