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
use Exception;

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
     * Email template references
     */
    const EMAIL_ACCOUNT_ACTIVATE = 1;
    const EMAIL_ACCOUNT_ACTIVATE_RESET_PASSWORD = 2;
    const EMAIL_DELETE_NOTIFICATION_1_WEEK = 3;
    const EMAIL_DELETE_NOTIFICATION_1_MONTH = 4;
    const EMAIL_FEEDBACK = 5;
    const EMAIL_LPA_REGISTRATION = 6;
    const EMAIL_NEW_EMAIL_ADDRESS_NOTIFY = 7;
    const EMAIL_NEW_EMAIL_ADDRESS_VERIFY = 8;
    const EMAIL_PASSWORD_CHANGED = 9;
    const EMAIL_PASSWORD_RESET = 10;
    const EMAIL_SENDGRID_BOUNCE = 11;

    private $emailTemplatesConfig = [
        self::EMAIL_ACCOUNT_ACTIVATE => [
            'template' => 'registration.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-signup',
            ],
        ],
        self::EMAIL_ACCOUNT_ACTIVATE_RESET_PASSWORD => [
            'template' => 'password-reset-not-active.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-activate',
            ],
        ],
        self::EMAIL_DELETE_NOTIFICATION_1_WEEK => [
            'template' => '',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-notification',
                'opg-lpa-notification-1-week-notice',
            ],
        ],
        self::EMAIL_DELETE_NOTIFICATION_1_MONTH => [
            'template' => 'account-deletion-notification.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-notification',
                'opg-lpa-notification-1-month-notice',
            ],
        ],
        self::EMAIL_FEEDBACK => [
            'template' => 'feedback.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-feedback',
            ],
        ],
        self::EMAIL_LPA_REGISTRATION => [
            'template' => 'lpa-registration.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-complete-registration',
            ],
        ],
        self::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY => [
            'template' => 'new-email-notify.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-newemail-confirmation',
            ],
        ],
        self::EMAIL_NEW_EMAIL_ADDRESS_VERIFY => [
            'template' => 'new-email-verify.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-newemail-verification',
            ],
        ],
        self::EMAIL_PASSWORD_CHANGED => [
            'template' => 'password-changed.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-password',
                'opg-lpa-password-changed',
            ],
        ],
        self::EMAIL_PASSWORD_RESET => [
            'template' => 'password-reset.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-normal',
            ],
        ],
        self::EMAIL_SENDGRID_BOUNCE => [
            'template' => 'bounce.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-autoresponse',
            ],
        ],
    ];

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
     * Create the content for the email reference and data provided
     *
     * @param $to
     * @param $emailRef
     * @param array $data
     * @param DateTime|null $sendAt
     * @throws Exception
     */
    public function sendMessageFromTemplate($to, $emailRef, array $data = [], DateTime $sendAt = null)
    {
        //  Get the categories for this email template
        if (!isset($this->emailTemplatesConfig[$emailRef])
            || !isset($this->emailTemplatesConfig[$emailRef]['template'])
            || !isset($this->emailTemplatesConfig[$emailRef]['categories'])) {

            throw new Exception('Missing template config for ' . $emailRef);
        }

        //  Get the HTML content from the template and the data
        $template = $this->emailRenderer->loadTemplate($this->emailTemplatesConfig[$emailRef]['template']);
        $emailHtml = $template->render($data);


        //  Construct the message to send
        $message = new Message();


        //  Add the TO address/addresses
        if (!is_array($to)) {
            $to = [$to];
        }

        foreach ($to as $toEmails) {
            $message->addTo($toEmails);
        }


        //  Set the FROM address - override where necessary for certain email types
        $from = $this->emailConfig['sender']['default']['address'];
        $fromName = $this->emailConfig['sender']['default']['name'];

        if ($emailRef == self::EMAIL_FEEDBACK) {
            $from = $this->emailConfig['sender']['feedback']['address'];
            $fromName = $this->emailConfig['sender']['feedback']['name'];
        } elseif ($emailRef == self::EMAIL_SENDGRID_BOUNCE) {
            $from = 'blackhole@lastingpowerofattorney.service.gov.uk';
        }


        $message->addFrom($from, $fromName);


        //  Add the categories for this message
        $categories = $this->emailTemplatesConfig[$emailRef]['categories'];

        foreach ($categories as $category) {
            $message->addCategory($category);
        }


        //  Get the subject from the template content and set it in the message
//TODO - confirm this definitely works...
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $emailHtml, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            throw new Exception('Email subject can not be retrieved from the email template content');
        }


        //  Set the HTML content as a mime message
        $html = new Mime\Part($emailHtml);
        $html->type = Mime\Mime::TYPE_HTML;
        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts([$html]);

        $message->setBody($mimeMessage);


        //  If a send time has been passed then apply that now
        if ($sendAt instanceof DateTime) {
            //  If a send time has been provided apply it now
            $message->setSendAt($sendAt->getTimestamp());
        }

        $this->send($message);
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
