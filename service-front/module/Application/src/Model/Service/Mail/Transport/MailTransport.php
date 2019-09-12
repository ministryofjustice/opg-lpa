<?php

namespace Application\Model\Service\Mail\Transport;

use Application\Model\Service\Mail\Message;
use Html2Text\Html2Text;
use Opg\Lpa\Logger\LoggerTrait;
use SendGrid;
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
    use LoggerTrait;

    /**
     * Mail client object
     *
     * @var SendGrid\Client
     */
    private $client;

    /**
     * Email renderer for sending template content
     *
     * @var Twig_Environment
     */
    private $emailRenderer;

    /**
     * @var array
     */
    private $emailConfig;

    /**
     * Email template references
     */
    const EMAIL_ACCOUNT_ACTIVATE                = 'email-account-activate';
    const EMAIL_ACCOUNT_ACTIVATE_PASSWORD_RESET = 'email-account-activate-reset-password';
    const EMAIL_FEEDBACK                        = 'email-feedback';
    const EMAIL_LPA_REGISTRATION                = 'email-lpa-registration';
    const EMAIL_NEW_EMAIL_ADDRESS_NOTIFY        = 'email-new-email-address-notify';
    const EMAIL_NEW_EMAIL_ADDRESS_VERIFY        = 'email-new-email-address-verify';
    const EMAIL_PASSWORD_CHANGED                = 'email-password-changed';
    const EMAIL_PASSWORD_RESET                  = 'email-password-reset';
    const EMAIL_PASSWORD_RESET_NO_ACCOUNT       = 'email-password-reset-no-account';
    const EMAIL_SENDGRID_BOUNCE                 = 'email-sendgrid-bounce';
    const EMAIL_ACCOUNT_DUPLICATION_WARNING     = 'email-account-duplication-warning';

    private $emailTemplatesConfig = [
        self::EMAIL_ACCOUNT_ACTIVATE => [
            'template' => 'registration.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-signup',
            ],
        ],
        self::EMAIL_ACCOUNT_ACTIVATE_PASSWORD_RESET => [
            'template' => 'password-reset-not-active.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-activate',
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
        self::EMAIL_PASSWORD_RESET_NO_ACCOUNT => [
            'template' => 'password-reset-no-account.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-noaccount',
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
        self::EMAIL_ACCOUNT_DUPLICATION_WARNING => [
            'template' => 'email-duplication-warning.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-signup-email-duplication',
            ],
        ],
    ];

    /**
     * MailTransport constructor
     *
     * @param SendGrid\Client $client
     * @param Twig_Environment $emailRenderer
     * @param array $emailConfig
     */
    public function __construct(SendGrid\Client $client, Twig_Environment $emailRenderer, array $emailConfig)
    {
        $this->client = $client;
        $this->emailRenderer = $emailRenderer;
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
                                $messagePlainText = new SendGrid\Content('text/plain', Html2Text::convert($part->getRawContent()));
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
            $email = new SendGrid\Mail($from, $message->getSubject(), new SendGrid\Email(null, $mainRecipient), $messagePlainText);

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
            $result = $this->client->mail()->send()->post($email);

            if (!in_array($result->statusCode(), [200, 202])) {
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
        //  Ensure the TO address/addresses are an array
        if (!is_array($to)) {
            $to = [$to];
        }

        try {
            $this->getLogger()->info(sprintf('Sending %s email to %s', $emailRef, implode(',', $to)));

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

            //  Log a final OK message
            $this->getLogger()->info(sprintf('%s email sent successfully to %s', $emailRef, implode(',', $to)));
        } catch (Exception $e) {
            //  Log the error with the data and rethrow
            $this->getLogger()->err(sprintf("Failed to send %s email to %s due to:\n%s", $emailRef, implode(',', $to), $e->getMessage()), $data);

            throw $e;
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
