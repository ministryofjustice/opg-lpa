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

    public function requestPasswordResetEmail( $email ){

        $client = $this->getServiceLocator()->get('ApiClient');

        /*
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


        var_dump($resetToken); exit();
        */

        $resetToken = 'c8ab95fec073f9a82cbc057a5d077bdc';

        //-------------------------------
        // Send the email

        $message = new MailMessage();

        $message->addFrom("neil.smith@digital.justice.gov.uk", "Neil Smith")
                    ->addTo("one@nsmith.net")
                    ->setSubject("My Subject! ".time());


        //$message->addCc("one@nsmith.me.uk");

        $message->addReplyTo("mrsmith@nsmith.net", "Neil Smith");

        //$message->setSender("jim@nsmith.net", "Jim");

        $message->getHeaders()->addHeaderLine('X-API-Key', 'FOO-BAR-BAZ-BAT');

        //--------------------

        $text = new MimePart("This is my plain text message.");
        $text->type = "text/plain";

        $html = new MimePart("<p>This is my HTML message</p>");
        $html->type = "text/html";

        //$image = new MimePart(fopen($pathToImage, 'r'));
        //$image->type = "image/jpeg";

        $body = new MimeMessage();
        $body->setParts(array($text, $html));

        $message->setBody($body);
        //$message->setBody("Just testing");

        //---

        $result = $this->getServiceLocator()->get('MailTransport')->send( $message );

        var_dump($result);

        die('here');

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
