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
                return trim( $body['reason'] );
            }

            return "unknown-error";

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

            return "failed-sending-email";

        }

        return true;


    } // function


    /**
     * Check if a given reset token is currently valid.
     *
     * @param $restToken
     * @return bool
     */
    public function isResetTokenValid( $restToken ){

        // If we can exchange it for a auth token, then it's valid.
        return is_string( $this->getAuthTokenFromRestToken( $restToken ) );

    } // function



    public function setNewPassword( $restToken, $password ){

        $authToken = $this->getAuthTokenFromRestToken( $restToken );

        if( !is_string( $authToken ) ){
            // error
            return false;
        }

        //---

        $client = $this->getServiceLocator()->get('ApiClient');

        // Set the new auth token on this client.
        $client->setToken( $authToken );

        $result = $client->updateAuthPassword( $password );

        //---

        if( $result !== true ){
            // error
        }

        //---

        return true;

    } // function

    //----------------------------------------------------

    /**
     * Exchange the reset token for an auth token.
     *
     * @param $restToken string The reset token.
     * @return bool|string Returns false on an error or the auth token on success.
     */
    private function getAuthTokenFromRestToken( $restToken ){

        return $this->getServiceLocator()->get('ApiClient')->requestPasswordResetAuthToken( $restToken );

    }  // function

} // class
