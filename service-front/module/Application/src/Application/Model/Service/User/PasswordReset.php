<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

class PasswordReset implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function requestPasswordResetEmail( $email, callable $routeCallback ){

        $client = $this->getServiceLocator()->get('ApiClient');

        $resetToken = $client->requestPasswordReset( $email );

        // A successful response is a string...
        if( !is_string($resetToken) ){

            // Error...
            $body = $client->getLastContent();

            if( isset($body['reason']) ){
                var_dump($body['reason']); exit();
            }

            # TODO - else we don't know what went wrong...

        } // if

        //-------------------------------
        // Send the email

        $message = new MailMessage();

        $message->addFrom('opg@lastingpowerofattorney.service.gov.uk', 'Office of the Public Guardian');

        $message->addTo( $email );

        $message->setSubject( 'Password reset request' );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-passwordreset');

        //---

        // Load the content from the view and merge in our variables...
        $content = $this->getServiceLocator()->get('EmailPhpRenderer')->render('password-reset', [
            // Use the passed callback to load the URL (the model should not be aware of how this is generated)
            'callback' => $routeCallback( $resetToken ),
        ]);

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

        }


    } // function

    public function isResetTokenValid( $restToken ){
        return is_string( $this->getAuthTokenFromRestToken( $restToken ) );
    } // function

    public function setNewPassword(){

    } // function

    private function getAuthTokenFromRestToken( $restToken ){


        return false;
    }  // function

} // class
