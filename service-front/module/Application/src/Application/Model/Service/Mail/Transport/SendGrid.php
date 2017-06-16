<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Message;
use Opg\Lpa\Logger\Logger;
use SendGrid as SendGridClient;
use SendGrid\Email as SendGridMessage;
use Zend\Mail\Exception\InvalidArgumentException;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Message as ZFMessage;
use Zend\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Message as MimeMessage;

/**
 * Sends an email out via SendGrid's HTTP interface.
 *
 * Class SendGrid
 * @package Application\Model\Mail\Transport
 */
class SendGrid implements TransportInterface
{
    /**
     * SendGrid client object
     *
     * @var SendGridClient
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(SendGridClient $client, Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Send a mail message
     *
     * @param  ZFMessage $message
     * @throws InvalidArgumentException
     * @throws TransportInvalidArgumentException
     */
    public function send(ZFMessage $message)
    {
        //  Determine the categories being used in the message
        $categories = ($message instanceof Message ? $message->getCategories() : []);

        try {
            if (!$message->isValid()) {
                throw new InvalidArgumentException('Mail\Message returns as invalid');
            }

            //  Start to create the email message
            $email = new SendGridMessage();

            //  Get the to addresses
            $toEmails = [];
            $toAddressList = $message->getTo();

            if (count($toAddressList) < 1) {
                throw new InvalidArgumentException('SendGrid requires at least one TO address');
            }

            foreach ($toAddressList as $address) {
                $toEmails[] = $address->getEmail();
                $email->addTo($address->getEmail());
            }

            //  Set the from address
            $from = $this->getFrom($message);
            $email->setFrom($from->getEmail())
                  ->setFromName($from->getName());

            //  Log the attempt to send the message
            $this->logger->info('Attempting to send email via SendGrid from ' . $email->getFrom() . ' to ' . implode(', ', $toEmails), [
                'categories' => $categories
            ]);

            //  Check that CC and BCC addresses have not been used
            if (count($message->getCc()) > 0 || count($message->getBcc()) > 0) {
                throw new InvalidArgumentException('SendGrid does not support CC or BCC addresses (but TO addresses are hidden from each other)');
            }

            //  Get the reply to address
            $replyTo = $message->getReplyTo();

            if (count($replyTo) == 1) {
                //  Extract the Address object
                $replyTo = array_pop(current($replyTo));

                $email->setReplyTo($replyTo->getEmail());
            } elseif (count($replyTo) > 1) {
                throw new InvalidArgumentException('SendGrid only supports a single REPLY TO address');
            }

            //  Set the subject for the message
            $email->setSubject($message->getSubject());

            // Custom Headers
            foreach ($message->getHeaders() as $header) {
                if ($header instanceof GenericHeader) {
                    $email->addHeader($header->getFieldName(), $header->getFieldValue());
                }
            }

            //  Add the categories to the email
            foreach ($categories as $category) {
                if (is_string($category)) {
                    $email->addCategory($category);
                }
            }

            //  If supported and required set send at
            if ($message instanceof Message§) {
                $sendAt = $message->getSendAt();

                if ($sendAt) {
                    $email->setSendAt($sendAt);
                    $email->setDate(date('r', $sendAt));
                }
            }

            // Set Content
            $plainTextSet = false;
            $htmlTextSet = false;

            $body = $message->getBody();

            // If $body is a string, then all we have is plain text, so we can just set it.
            if (is_string($body)) {
                $email->setText($body);
                $plainTextSet = true;
            } elseif ($body instanceof MimeMessage) {
                foreach ($body->getParts() as $part) {
                    switch ($part->type) {
                        case 'text/plain':
                            if ($plainTextSet) {
                                throw new InvalidArgumentException("SendGrid only supports a single plain text body");
                            }

                            $email->setText($part->getRawContent());
                            $plainTextSet = true;
                            break;
                        case 'text/html':
                            if ($htmlTextSet) {
                                throw new InvalidArgumentException("SendGrid only supports a single HTML body");
                            }

                            $email->setHtml($part->getRawContent());
                            $htmlTextSet = true;
                            break;
                        default:
                            throw new InvalidArgumentException("Unimplemented content part found: {$part->type}");
                    }
                }
            }

            // Ensure some content was set
            if (!$plainTextSet && !$htmlTextSet) {
                throw new InvalidArgumentException("No message content has been set");
            }

            // Send message
            $result = $this->client->send($email);

            if ($result->message != 'success') {
                throw new TransportInvalidArgumentException("Email sending failed: {$result->message}");
            }
        } catch (InvalidArgumentException $iae) {
            //  Log an appropriate error message and rethrow the exception
            $this->logger->err('SendGrid transport error: ' . $iae->getMessage(), [
                'categories' => $categories
            ]);

            throw $iae;
        }
    }

    /**
     * Get the from address object from the message
     *
     * @param  ZFMessage $message
     * @return mixed|\Zend\Mail\Address
     * @throws InvalidArgumentException
     */
    private function getFrom(ZFMessage $message)
    {
        $from = $message->getFrom();

        if (count($from) > 1) {
            throw new InvalidArgumentException('SendGrid only supports a single FROM address');
        }

        //  Extract the Address object
        $from = current(current($from));

        //  However (due to crazy RFC822-ness) if a 'sender' has been set, this should be used instead.
        if ($message->getSender() != null) {
            $from = $message->getSender();
        }

        return $from;
    }
}
