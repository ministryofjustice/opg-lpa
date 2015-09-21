<?php
namespace Application\Model\Service\Mail\Transport;

use SendGrid as SendGridClient;
use SendGrid\Email as SendGridMessage;

use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Exception\InvalidArgumentException;
use Zend\Mail\Header\GenericHeader;

use Zend\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;

/**
 * Sends an email out via SendGrid's HTTP interface.
 *
 * Class SendGrid
 * @package Application\Model\Mail\Transport
 */
class SendGrid implements TransportInterface {

    private $client;

    public function __construct( SendGridClient $client ){
        $this->client = $client;
    }

    /**
     * Send a mail message
     *
     * @param Message $message
     * @throws InvalidArgumentException
     * @throws TransportInvalidArgumentException
     */
    public function send( Message $message ){

        if( !$message->isValid() ) { throw new InvalidArgumentException('Mail\Message returns as invalid'); }

        //---

        $email = new SendGridMessage();

        //--------------------------------
        // From

        $from = $message->getFrom();

        if( count($from) > 1 ){ throw new InvalidArgumentException('SendGrid only supports a single FROM address'); }

        // Extract the Address object...
        $from = array_pop( current($from) );

        // However (due to crazy RFC822-ness) if a 'sender' has been set, this should be used instead.
        if( $message->getSender() != null ){
            $from = $message->getSender();
        }

        $email->setFrom( $from->getEmail() )->setFromName( $from->getName() );

        //--------------------------------
        // To

        $toList = $message->getTo();

        if( count($toList) < 1 ){
            throw new InvalidArgumentException('SendGrid requires at least one TO address');
        }

        foreach( $toList as $address ){
            $email->addTo( $address->getEmail() );
        }

        //--------------------------------
        // CC

        $toList = $message->getCc();

        foreach( $toList as $address ){
            throw new InvalidArgumentException('SendGrid does not support CC addresses (but TO addresses are hidden from each other)');
            $email->addCc( $address->getEmail() );
        }

        //--------------------------------
        // BCC

        $toList = $message->getBcc();

        foreach( $toList as $address ){
            throw new InvalidArgumentException('SendGrid does not support BCC addresses (but TO addresses are hidden from each other)');
            $email->addBcc( $address->getEmail() );
        }

        //--------------------------------
        // ReplyTo

        $replyTo = $message->getReplyTo();

        if( count($replyTo) == 1 ){

            // Extract the Address object...
            $replyTo = array_pop( current($replyTo) );

            $email->setReplyTo( $replyTo->getEmail() );

        } elseif( count($replyTo) > 1 ){

            throw new InvalidArgumentException('SendGrid only supports a single REPLY TO address');

        }

        //--------------------------------
        // Subject

        $email->setSubject( $message->getSubject() );

        //--------------------------------
        // Custom Headers

        foreach( $message->getHeaders() as $header ){
            if( $header instanceof GenericHeader ){
                $email->addHeader( $header->getFieldName(), $header->getFieldValue() );
            }
        }

        //--------------------------------
        // Categories

        // If the messages supports Categories...
        if( is_callable( [ $message, 'getCategories' ] ) ){

            // This requires getCategories() to return an array of strings.

            $categories = $message->getCategories();

            if( is_array( $categories ) ){

                foreach( $categories as $category ){
                    if( is_string( $category ) ){
                        $email->addCategory( $category );
                    }
                } // foreach

            } // if
            
            if( is_callable( [ $message, 'getSendAt' ] ) ) {
                $sendAt = $message->getSendAt();
                if ($sendAt) {
                    $email->setSendAt($sendAt);
                }
            } // if
            

        } // if

        //--------------------------------
        // Set Content

        $plainTextSet = false;
        $htmlTextSet = false;

        $body = $message->getBody();

        // If $body is a string, then all we have is plain text, so we can just set it.
        if( is_string( $body ) ){

            $email->setText( $body );
            $plainTextSet = true;

        } elseif ( $body instanceof MimeMessage ) {

            foreach( $body->getParts() as $part ){

                switch( $part->type ){

                    case 'text/plain':
                        if( $plainTextSet ){
                            throw new InvalidArgumentException("SendGrid only supports a single plain text body");
                        }
                        $email->setText( $part->getRawContent() );
                        $plainTextSet = true;
                        break;

                    case 'text/html':
                        if( $htmlTextSet ){
                            throw new InvalidArgumentException("SendGrid only supports a single HTML body");
                        }
                        $email->setHtml( $part->getRawContent() );
                        $htmlTextSet = true;
                        break;

                    default:
                        throw new InvalidArgumentException("Unimplemented content part found: {$part->type}");

                } // switch

            } // foreach part

        } // if

        // Ensure some content was set
        if( !( $plainTextSet || $htmlTextSet ) ){
            throw new InvalidArgumentException("No message content has been set");
        }

        //--------------------------------
        // Send message

        // Send the message...
        $result = $this->client->send( $email );

        if( $result->message != 'success' ){
            throw new TransportInvalidArgumentException("Email sending failed: {$result->message}");
        }

    } // function

} // class
