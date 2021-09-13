<?php

namespace Application\Model\Service\Mail\Transport;

use Html2Text\Html2Text;
use Application\Logging\LoggerTrait;
use SendGrid;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Header\GenericHeader;
use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use DateTime;
use Exception;
use Application\View\Helper\RendererInterface as RendererInterface;

/**
 * Sends an email out via SendGrid's HTTP interface.
 *
 * Class MailTransport
 * @package Application\Model\Mail\Transport
 */
class SendGridMailTransport implements TransportInterface
{
    use LoggerTrait;

    /**
     * Mail client object
     *
     * @var SendGrid\Client
     */
    private $client;

    /**
     * MailTransport constructor
     *
     * @param SendGrid\Client $client
     */
    public function __construct(SendGrid\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send a mail message
     *
     * @param  LaminasMessage $message
     * @throws Laminas\Mail\Exception\ExceptionInterface
     *
     * TODO any classes which reference this method should either catch ExceptionInterface
     * or specific exception types
     */
    public function send(LaminasMessage $message)
    {
        //  Determine the categories being used in the message
        $categories = ($message instanceof Message ? $message->getCategories() : []);

        try {
            if (!$message->isValid()) {
                throw new InvalidArgumentException('Mail\Message returns as invalid');
            }

            //  Get the "from" address
            $from = $this->getFrom($message);

            //  Get the "to" address(es)
            $toEmails = [];
            $toAddressList = $message->getTo();

            if (count($toAddressList) < 1) {
                throw new InvalidArgumentException('SendGrid requires at least one TO address');
            }

            foreach ($toAddressList as $address) {
                $toEmails[] = $address->getEmail();
            }

            //  Log the attempt to send the message
            $this->getLogger()->info('Attempting to send email via SendGrid', [
                'from-address' => $from->getEmail(),
                'to-address'   => $toEmails,
                'categories'   => $categories
            ]);

            $from = new SendGrid\Email($from->getName(), $from->getEmail());

            //  Parse the message content to get the HTML and plain text versions
            $messagePlainText = null;
            $messageHtml = null;

            $messageBody = $message->getBody();

            // If $messageBody is a string, then all we have is plain text, so we can just set it.
            if (is_string($messageBody)) {
                $messagePlainText = $messageBody;
            } elseif ($messageBody instanceof MimeMessage) {
                foreach ($messageBody->getParts() as $part) {
                    switch ($part->type) {
                        case 'text/plain':
                            if (!is_null($messagePlainText)) {
                                throw new InvalidArgumentException("SendGrid only supports a single plain text body");
                            }

                            $messagePlainText = new SendGrid\Content($part->type, $part->getRawContent());
                            break;
                        case 'text/html':
                            if (!is_null($messageHtml)) {
                                throw new InvalidArgumentException("SendGrid only supports a single HTML body");
                            }

                            if (is_null($messagePlainText)) {
                                $messagePlainText = new SendGrid\Content(
                                    'text/plain',
                                    Html2Text::convert($part->getRawContent())
                                );
                            }

                            $messageHtml = new SendGrid\Content($part->type, $part->getRawContent());
                            break;
                        default:
                            throw new InvalidArgumentException("Unimplemented content part found: {$part->type}");
                    }
                }
            }

            //  Ensure some content was set
            if (is_null($messagePlainText) && is_null($messageHtml)) {
                throw new InvalidArgumentException("No message content has been set");
            }

            //  Create the email message using the plain text initially
            $mainRecipient = array_shift($toEmails);
            $email = new SendGrid\Mail(
                $from,
                $message->getSubject(),
                new SendGrid\Email(null, $mainRecipient),
                $messagePlainText
            );

            //  Add the HTML content
            $email->addContent($messageHtml);

            //  Add other "to" email addresses
            foreach ($toEmails as $toEmail) {
                $email->personalization[0]->addTo(new SendGrid\Email(null, $toEmail));
            }

            //  Get the reply to address
            $replyTo = $message->getReplyTo();

            if (count($replyTo) == 1) {
                //  Extract the Address object
                $replyTo = array_pop(current($replyTo));
                $replyTo = new SendGrid\Email(null, $replyTo->getEmail());

                $email->setReplyTo($replyTo);
            } elseif (count($replyTo) > 1) {
                throw new InvalidArgumentException('SendGrid only supports a single REPLY TO address');
            }

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
            if ($message instanceof Message) {
                $sendAt = $message->getSendAt();

                if ($sendAt) {
                    $email->setSendAt($sendAt);
                }
            }

            //  Send message
            //  TODO catch SendGrid\Exception\InvalidRequest;
            //  convert into Laminas\Mail\Transport\Exception\RuntimeException
            //  (which extends Laminas\Mail\Exception\ExceptionInterface)
            $result = $this->client->mail()->send()->post($email);

            if (!in_array($result->statusCode(), [200, 202])) {
                // TODO this exception type doesn't seem wholly appropriate
                throw new TransportInvalidArgumentException('Email sending failed: ' . $result->body());
            }
        } catch (InvalidArgumentException $iae) {
            //  Log an appropriate error message and rethrow the exception
            $this->getLogger()->err('SendGrid transport error: ' . $iae->getMessage(), [
                'categories' => $categories
            ]);

            throw $iae;
        }
    }

    /**
     * Get the from address object from the message
     *
     * @param  LaminasMessage $message
     * @return mixed|\Laminas\Mail\Address
     * @throws InvalidArgumentException
     */
    private function getFrom(LaminasMessage $message)
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
