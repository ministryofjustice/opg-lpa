<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Message;
use Html2Text\Html2Text;
use Opg\Lpa\Logger\Logger;
use SendGrid as SendGridClient;
use SendGrid\Email as SendGridMessage;
use Twig_Environment;
use Zend\Mime;
use Zend\Mail\Exception\InvalidArgumentException;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Message as ZFMessage;
use Zend\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Message as MimeMessage;
use DateTime;

/**
 * Sends an email out via SendGrid's HTTP interface.
 *
 * Class MailTransport
 * @package Application\Model\Mail\Transport
 */
class MailTransport implements TransportInterface
{

    /**
     * Mail client object
     *
     * @var SendGridClient
     */
    private $client;

    /**
     * Email renderer for sending template content
     *
     * @var Twig_Environment
     */
    private $emailRenderer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $emailConfig;

    /**
     * MailTransport constructor
     *
     * @param SendGridClient $client
     * @param Twig_Environment $emailRenderer
     * @param Logger $logger
     */
    public function __construct(SendGridClient $client, Twig_Environment $emailRenderer, Logger $logger, array $emailConfig)
    {
        $this->client = $client;
        $this->emailRenderer = $emailRenderer;
        $this->logger = $logger;
        $this->emailConfig = $emailConfig;
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
            $this->logger->info('Attempting to send email via SendGrid', [
                'from-address' => $email->getFrom(),
                'to-address'   => $toEmails,
                'categories'   => $categories
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
//TODO - the presence of the § suggests that this send at functionality has never worked
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

                            $html = $part->getRawContent();
                            $email->setHtml($html);
                            $htmlTextSet = true;

                            if (!$plainTextSet) {
                                $text = Html2Text::convert($html);
                                $email->setText($text);
                                $plainTextSet = true;
                            }
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
     * @param $to
     * @param array $categories
     * @param $subject
     * @param $emailHtml
     * @param DateTime|null $sendAt
     */
    public function sendMessage($to, array $categories, $subject, $emailHtml, DateTime $sendAt = null)
    {

//TODO - potentially fold this code into the "send" function above....

        $email = new Message();

        //to
        $email->addTo($to);

        //from
        $from = $this->emailConfig['sender']['default']['address'];
        $fromName = $this->emailConfig['sender']['default']['name'];
        $email->addFrom($from, $fromName);

        foreach ($categories as $category) {
            $email->addCategory($category);
        }

        $email->setSubject($subject);

        //  Set the HTML content as a mime message
        $html = new Mime\Part($emailHtml);

        $html->type = Mime\Mime::TYPE_HTML;

        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts([$html]);

        $email->setBody($mimeMessage);

        if ($sendAt instanceof DateTime) {
            //  If a send time has been provided apply it now
            $email->setSendAt($sendAt->getTimestamp());
        }

        $this->send($email);
    }

    /**
     * Create the content using the template and data and pass to send message function
     *
     * @param $to
     * @param array $categories
     * @param $subject
     * @param $template
     * @param $data
     * @param DateTime|null $sendAt
     */
    public function sendMessageFromTemplate($to, array $categories, $subject, $template, $data, DateTime $sendAt = null)
    {
        //  Render the content as HTML
        $template = $this->emailRenderer->loadTemplate($template);

        $emailHtml = $template->render($data);

        //  If the template has a preferred subject in the content use that instead
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $emailHtml, $matches) === 1) {
            $subject = $matches[1];
        }

        $this->sendMessage($to, $categories, $subject, $emailHtml, $sendAt);
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
