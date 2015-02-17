<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

class Register implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function registerAccount( $email, $password, callable $routeCallback ){

        $client = $this->getServiceLocator()->get('ApiClient');

        $activationToken = $client->registerAccount( strtolower($email), $password );

        // A successful response is a string...
        if( !is_string($activationToken) ){

            // Error...
            $body = $client->getLastContent();

            if( isset($body['reason']) ){
                return trim( $body['reason'] );
            } elseif( isset($body['error_description']) ){
                return trim( $body['error_description'] );
            }

            return "unknown-error";

        } // if

        //-------------------------------
        // Send the email

        $message = new MailMessage();

        $message->addFrom('opg@lastingpowerofattorney.service.gov.uk', 'Office of the Public Guardian');

        $message->addTo( $email );

        $message->setSubject( 'Activate your lasting power of attorney registration' );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-registration');

        //---

        // Load the content from the view and merge in our variables...
        $content = $this->getServiceLocator()->get('EmailPhpRenderer')->render('registration', [
            // Use the passed callback to load the URL (the model should not be aware of how this is generated)
            'callback' => $routeCallback( $activationToken ),
        ]);

        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        //---

        try {

            $this->getServiceLocator()->get('MailTransport')->send($message);

        } catch ( \Exception $e ){

            return "failed-sending-email";

        }

        return true;

    } // function


    /**
     * Activate an account. i.e. confirm the email address.
     *
     * @param $token
     */
    public function activateAccount( $token ){

        $client = $this->getServiceLocator()->get('ApiClient');

        /**
         * This returns:
         *      TRUE - If the user account exists. The account has been activated, or was already activated.
         *      FALSE - If the user account does not exist.
         *
         *  Alas no other details are returned.
         */
        $success = $client->activateAccount( $token );

        return $success;

    } // function


} // class
