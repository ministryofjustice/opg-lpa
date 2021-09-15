<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Application\Model\Service\Mail\Message;
use Application\View\Helper\LocalViewRenderer;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime;
use Twig\Environment;

abstract class AbstractEmailService extends AbstractService
{
    /**
     * @var Environment
     */
    private $localViewRenderer;

    /**
     * @var TransportInterface
     */
    private $mailTransport;

    /**
     * Email template references
     */
    public const EMAIL_ACCOUNT_ACTIVATE                = 'email-account-activate';
    public const EMAIL_ACCOUNT_ACTIVATE_PASSWORD_RESET = 'email-account-activate-reset-password';
    public const EMAIL_FEEDBACK                        = 'email-feedback';
    public const EMAIL_LPA_REGISTRATION                = 'email-lpa-registration';
    public const EMAIL_NEW_EMAIL_ADDRESS_NOTIFY        = 'email-new-email-address-notify';
    public const EMAIL_NEW_EMAIL_ADDRESS_VERIFY        = 'email-new-email-address-verify';
    public const EMAIL_PASSWORD_CHANGED                = 'email-password-changed';
    public const EMAIL_PASSWORD_RESET                  = 'email-password-reset';
    public const EMAIL_PASSWORD_RESET_NO_ACCOUNT       = 'email-password-reset-no-account';
    public const EMAIL_SENDGRID_BOUNCE                 = 'email-sendgrid-bounce';
    public const EMAIL_ACCOUNT_DUPLICATION_WARNING     = 'email-account-duplication-warning';

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
     * AbstractEmailService constructor.
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param LocalViewRenderer $localViewRenderer
     * @param TransportInterface $mailTransport
     */
    public function __construct(
        AuthenticationService $authenticationService,
        array $config,
        LocalViewRenderer $localViewRenderer,
        TransportInterface $mailTransport
    ) {
        parent::__construct($authenticationService, $config);
        $this->localViewRenderer = $localViewRenderer;
        $this->mailTransport = $mailTransport;
    }

    /**
     * Map a template name to a template file name and categories.
     *
     * @param string $emailRef A key of $this->emailTemplatesConfig.
     * @return ?array with template and categories; null if template ref
     * is not found
     */
    private function getTemplate($emailRef): ?array
    {
        if (
            !isset($this->emailTemplatesConfig[$emailRef])
            || !isset($this->emailTemplatesConfig[$emailRef]['template'])
            || !isset($this->emailTemplatesConfig[$emailRef]['categories'])
        ) {
            return null;
        }

        return $this->emailTemplatesConfig[$emailRef];
    }

    /**
     * @return TransportInterface
     */
    public function getMailTransport(): TransportInterface
    {
        return $this->mailTransport;
    }

    /**
     * Create the content for the email reference and data provided
     *
     * @param $to
     * @param $emailRef
     * @param array $data
     * @return Message
     * @throws InvalidArgumentException
     */
    public function createMessage($to, $emailRef, array $data = []): Message
    {
        //  Ensure the TO address/addresses are an array
        if (!is_array($to)) {
            $to = [$to];
        }

        $this->getLogger()->info(sprintf('Sending %s email to %s', $emailRef, implode(',', $to)));

        $template = $this->getTemplate($emailRef);
        if (is_null($template)) {
            throw new InvalidArgumentException('Missing template config for ' . $emailRef);
        }

        //  Get the HTML content from the template and the data
        $emailHtml = $this->localViewRenderer->renderTemplate($template['template'], $data);

        //  Construct the message to send
        $message = new Message();

        foreach ($to as $toEmails) {
            $message->addTo($toEmails);
        }

        $emailConfig = $this->getConfig()['email'];

        //  Set the FROM address - override where necessary for certain email types
        $from = $emailConfig['sender']['default']['address'];
        $fromName = $emailConfig['sender']['default']['name'];

        if ($emailRef == self::EMAIL_FEEDBACK) {
            $from = $emailConfig['sender']['feedback']['address'];
            $fromName = $emailConfig['sender']['feedback']['name'];
        } elseif ($emailRef == self::EMAIL_SENDGRID_BOUNCE) {
            $from = 'blackhole@lastingpowerofattorney.service.gov.uk';
        }

        $message->addFrom($from, $fromName);

        //  Add the categories for this message
        foreach ($template['categories'] as $category) {
            $message->addCategory($category);
        }

        //  Get the subject from the template content and set it in the message
        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $emailHtml, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            throw new InvalidArgumentException('Email subject can not be retrieved from the email template content');
        }

        //  Set the HTML content as a mime message
        $html = new Mime\Part($emailHtml);
        $html->type = Mime\Mime::TYPE_HTML;
        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts([$html]);

        $message->setBody($mimeMessage);

        return $message;
    }
}
