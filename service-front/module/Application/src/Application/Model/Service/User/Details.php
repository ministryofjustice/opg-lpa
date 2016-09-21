<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Application\Model\Service\ServiceDataInputInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

use Opg\Lpa\Api\Client\Exception\ResponseException as ApiClientError;

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

        $details = $details->getDataForModel();

        // Load the existing details...
        $userDetails = $client->getAboutMe();

        // Apply the new ones...
        $userDetails->populateWithFlatArray( $details );

        //---

        // Check if the user has removed their address
        if( array_key_exists( 'address', $details ) && $details['address'] == null ){
            $userDetails->address = null;
        }

        // Check if the user has removed their DOB
        if( !isset( $details['dob-date'] ) ){
            $userDetails->dob = null;
        }

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
     * @param Callback function $activateEmailCallback
     * @param string $currentAddress
     * @param string $userId
     * 
     * @return bool|string
     */
    public function requestEmailUpdate( 
        ServiceDataInputInterface $details, 
        $activateEmailCallback, 
        $currentAddress, 
        $userId 
    ){
        
        $identityArray = $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray();
        
        $this->getServiceLocator()->get('Logger')->info(
            'Requesting email update to new email: ' . $details->getDataForModel()['email'],
            $identityArray    
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $updateToken = $client->requestEmailUpdate( strtolower($details->getDataForModel()['email']) );

        //---

        if( !is_string($updateToken) ){


            if( $updateToken instanceof ApiClientError ){

                switch ( $updateToken->getDetail() ){
                    case 'User already has this email' :
                        return 'user-already-has-email';

                    case 'Email already exists for another user':
                        return 'email-already-exists';
                }

            } // if

            return "unknown-error";

        } // if

        $this->sendNotifyNewEmailEmail( $currentAddress, $details->getDataForModel()['email'] );
        
        return $this->sendActivateNewEmailEmail( $details->getDataForModel()['email'], $activateEmailCallback( $userId, $updateToken ) );


    } // function
    
    function sendActivateNewEmailEmail( $newEmailAddress, $activateUrl ) {
        
        $this->getServiceLocator()->get('Logger')->info(
            'Sending new email verification email'
        );
        
        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
        
        $message->addTo( $newEmailAddress );
        
        //---
        
        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-newemail-verification');
        
        //---
        
        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('new-email-verify.twig')->render([
            'activateUrl' => $activateUrl,
        ]);
        
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('Please verify your new email address');
        }
        
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
    
    function sendNotifyNewEmailEmail( $oldEmailAddress, $newEmailAddress ) {
    
        $this->getServiceLocator()->get('Logger')->info(
            'Sending new email confirmation email'
        );
    
        $message = new MailMessage();
    
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
    
        $message->addTo( $oldEmailAddress );
    
        //---
    
        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-newemail-confirmation');
    
        //---
    
        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('new-email-notify.twig')->render([
            'newEmailAddress' => $newEmailAddress,
        ]);
        
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject( 'You asked us to change your email address' );
        }
        
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

        $success = $client->updateAuthEmail( $emailUpdateToken );
        
        return $success === true;
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

        $email = $client->getEmail();

        $result = $client->updateAuthPassword(
            $details->getDataForModel()['password_current'],
            $details->getDataForModel()['password'] 
        );

        //---

        if( !is_string($result) ){

            return 'unknown-error';

        } // if

        //---

        $this->sendPasswordUpdatedEmail( $email );

        //---

        // Update the identity with the new token to avoid being
        // logged out after the redirect. We don't need to update the token
        // on the API client because this will happen on the next request
        // when it reads it from the identity.
        $identity->setToken($result);
        
        return true;

    } // function

    public function sendPasswordUpdatedEmail( $email ){

        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo( $email );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-password');
        $message->addCategory('opg-lpa-password-changed');

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('password-changed.twig')->render([
            'email' => $email
        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject( 'You have changed your LPA account password' );
        }

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

    } // function

} // class