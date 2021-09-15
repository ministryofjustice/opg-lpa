<?php

namespace Application\Model\Service\Mail\Transport;

use Html2Text\Html2Text;
use SendGrid;
use SendGrid\Exception\InvalidRequest;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Header\GenericHeader;
use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mail\Transport\Exception\InvalidArgumentException as TransportInvalidArgumentException;
use Laminas\Mail\Transport\Exception\RuntimeException;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use DateTime;
use Exception;
use Application\Logging\LoggerTrait;
use Application\Model\Service\Mail\Mail;
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
     * @throws Laminas\Mail\Exception\ExceptionInterface (InvalidArgumentException |
     * TransportInvalidArgumentException | RuntimeException)
     */
    public function send(LaminasMessage $message)
    {
        // Determine the categories being used in the message
        $categories = ($message instanceof Message ? $message->getCategories() : []);

        try {
            if (!$message->isValid()) {
                throw new InvalidArgumentException('Mail\Message returns as invalid');
            }

            // Get the "to" address(es)
            $toAddressList = $message->getTo();

            if (count($toAddressList) < 1) {
                throw new InvalidArgumentException('SendGrid requires at least one TO address');
            }

            $toEmails = [];
            foreach ($toAddressList as $address) {
                $toEmails[] = $address->getEmail();
            }

            // Get the "from" address
            $from = $this->getFrom($message);

            $from = new SendGrid\Email($from->getName(), $from->getEmail());

            // Parse the message content to get the HTML and plain text versions
            $messagePlainText = null;
            $messageHtml = null;

            $messageBody = $message->getBody();

            // If $messageBody is a string, then all we have is plain text, so we can just set it.
            if (is_string($messageBody)) {
                $messagePlainText = $messageBody;
            } elseif ($messageBody instanceof MimeMessage) {
                // If multiple text/plain and/or text/html parts are present, the last of each used;
                // if text/html is present and text/plain is not set at the point when it
                // is encountered, text/plain content is overridden with the plain text
                // version of what's in text/html.
                //
                // If a mime part is not text/plain or text/html, it's ignored, as SendGrid doesn't
                // support it.
                foreach ($messageBody->getParts() as $part) {
                    $type = $part->type;

                    if ($type === 'text/plain') {
                        $messagePlainText = new SendGrid\Content($part->type, $part->getRawContent());
                    } elseif ($type === 'text/html') {
                        if (is_null($messagePlainText)) {
                            $messagePlainText = new SendGrid\Content(
                                'text/plain',
                                Html2Text::convert($part->getRawContent())
                            );
                        }

                        $messageHtml = new SendGrid\Content($part->type, $part->getRawContent());
                    }
                }
            }

            // Ensure some content was set
            if (is_null($messagePlainText) && is_null($messageHtml)) {
                throw new InvalidArgumentException("No message content has been set");
            }

            // Create the email message using the plain text initially
            $mainRecipient = $toEmails[0];
            $email = new SendGrid\Mail(
                $from,
                $message->getSubject(),
                new SendGrid\Email(null, $mainRecipient),
                $messagePlainText
            );

            // Add the HTML content
            $email->addContent($messageHtml);

            // Add other "to" email addresses
            foreach (array_slice($toEmails, 1) as $toEmail) {
                $email->personalization[0]->addTo(new SendGrid\Email(null, $toEmail));
            }

            // Add the categories to the email
            foreach ($categories as $category) {
                if (is_string($category)) {
                    $email->addCategory($category);
                }
            }

            // Log the attempt to send the message
            $this->getLogger()->info('Attempting to send email via SendGrid', [
                'from-address' => $from->getEmail(),
                'to-address' => $toEmails,
                'categories' => $categories
            ]);

            // Send message
            // May throw SendGrid\Exception\InvalidRequest exception (undocumented)
            $result = $this->client->mail()->send()->post($email);

            if (!in_array($result->statusCode(), [200, 202])) {
                throw new TransportInvalidArgumentException('Email sending failed: ' . $result->body());
            }
        } catch (InvalidRequest $ex) {
            $this->getLogger()->err(
                'SendGrid transport error; likely to be networking or auth related: ' . $ex->getMessage(),
                [
                    'categories' => $categories
                ]
            );

            // throw as an exception which extends ExceptionInterface
            throw new RuntimeException($ex);
        } catch (InvalidArgumentException $ex) {
            // Log error and rethrow the exception
            $this->getLogger()->err('SendGrid transport error: ' . $ex->getMessage(), [
                'categories' => $categories
            ]);

            throw $ex;
        }
    }

    /**
     * Get the from address object from the message.
     * If multiple from addresses are provided, the first is used.
     *
     * @param  LaminasMessage $message
     * @return mixed|\Laminas\Mail\Address
     */
    private function getFrom(LaminasMessage $message)
    {
        $from = $message->getFrom();

        //  Extract the Address object
        $from = current(current($from));

        //  However (due to crazy RFC822-ness) if a 'sender' has been set, this should be used instead.
        if ($message->getSender() != null) {
            $from = $message->getSender();
        }

        return $from;
    }
}
