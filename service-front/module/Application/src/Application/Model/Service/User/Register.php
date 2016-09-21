<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

use Opg\Lpa\Api\Client\Exception\ResponseException as ApiClientError;

class Register implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function registerAccount( $email, $password, callable $routeCallback ){

        $this->getServiceLocator()->get('Logger')->info('Account registration attempt for ' . $email);
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $activationToken = $client->registerAccount( strtolower($email), $password );

        // A successful response is a string...
        if( !is_string($activationToken) ){

            if( $activationToken instanceof ApiClientError ){

                if( $activationToken->getDetail() == 'username-already-exists' ){
                    return "address-already-registered";
                }

                return $activationToken->getDetail();

            }

            return "unknown-error";

        } // if

        //-------------------------------
        // Send the email

        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo( $email );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-signup');

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('registration.twig')->render(
            ['callback' => $routeCallback( $activationToken ),
        ]);
        
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject( 'Activate your lasting power of attorney account' );
        }
        
        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        //---

        try {

            $this->getServiceLocator()->get('Logger')->info(
                'Sending account registration email to ' . $email
            );
            
            $this->getServiceLocator()->get('MailTransport')->send($message);

        } catch ( \Exception $e ){

            $this->getServiceLocator()->get('Logger')->err(
                'Failed to send account registration email to ' . $email
            );
            
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
         *      TRUE - If the user account exists. The account has been activated.
         *      ApiClientError - If the user account does not exist, or was already activated.
         *
         */
        $result = $client->activateAccount( $token );

        if ( $result === true ) {
            $this->getServiceLocator()->get('Logger')->info(
                'Account activation attempt with token was successful'
            );
        } else {
            $this->getServiceLocator()->get('Logger')->info(
                'Account activation attempt with token failed, or was already activated'
            );
        }
        
        return ( $result === true );

    } // function


} // class
