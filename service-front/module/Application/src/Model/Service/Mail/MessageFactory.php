<?php

namespace Application\Model\Service\Mail;

use MakeShared\Logging\LoggerTrait;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Message;
use Application\View\Helper\LocalViewRenderer;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use Laminas\Mime;

/**
 * Create Message instances, setting the body via a rendered Twig template.
 */
class MessageFactory
{
    use LoggerTrait;

    /** @var LocalViewRenderer */
    private $localViewRenderer;

    /** @var array */
    private $emailTemplatesConfig;

    /** @var array */
    private $config;

    private $defaultMailTemplatesConfig = [
        AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE => [
            'template' => 'registration.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-signup',
            ],
        ],
        AbstractEmailService::EMAIL_FEEDBACK => [
            'template' => 'feedback.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-feedback',
            ],
        ],
        AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1 => [
            'template' => 'lpa-registration-with-payment.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-complete-registration',
            ],
        ],
        AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 => [
            'template' => 'lpa-registration-with-payment.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-complete-registration',
            ],
        ],
        AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3 => [
            'template' => 'lpa-registration-with-payment.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-complete-registration',
            ],
        ],
        AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY => [
            'template' => 'new-email-notify.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-newemail-confirmation',
            ],
        ],
        AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_VERIFY => [
            'template' => 'new-email-verify.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-newemail-verification',
            ],
        ],
        AbstractEmailService::EMAIL_PASSWORD_CHANGED => [
            'template' => 'password-changed.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-password',
                'opg-lpa-password-changed',
            ],
        ],
        AbstractEmailService::EMAIL_PASSWORD_RESET => [
            'template' => 'password-reset.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-normal',
            ],
        ],
        AbstractEmailService::EMAIL_PASSWORD_RESET_NO_ACCOUNT => [
            'template' => 'password-reset-no-account.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-passwordreset',
                'opg-lpa-passwordreset-noaccount',
            ],
        ],
        AbstractEmailService::EMAIL_ACCOUNT_DUPLICATION_WARNING => [
            'template' => 'email-duplication-warning.twig',
            'categories' => [
                'opg',
                'opg-lpa',
                'opg-lpa-signup-email-duplication',
            ],
        ],
    ];

    /**
     * MessageFactory constructor.
     *
     * @param array $config Config for the whole app; should contain
     * @param LocalViewRenderer $localViewRenderer
     * @param array $emailTemplatesConfig Configuration for email
     * templates; if not set, default config is used
     */
    public function __construct(
        array $config,
        LocalViewRenderer $localViewRenderer,
        array $emailTemplatesConfig = null
    ) {
        $this->config = $config;
        $this->localViewRenderer = $localViewRenderer;

        if (is_null($emailTemplatesConfig)) {
            $emailTemplatesConfig = $this->defaultMailTemplatesConfig;
        }
        $this->emailTemplatesConfig = $emailTemplatesConfig;
    }

    /**
     * Map a template name to a template file name and categories.
     *
     * @param string $templateRef A key of $this->emailTemplatesConfig.
     * @return array|null with template and categories; null if template ref
     * is not found
     */
    private function getTemplate($templateRef): array|null
    {
        if (
            !isset($this->emailTemplatesConfig[$templateRef])
            || !isset($this->emailTemplatesConfig[$templateRef]['template'])
            || !isset($this->emailTemplatesConfig[$templateRef]['categories'])
        ) {
            return null;
        }

        return array($this->emailTemplatesConfig[$templateRef]);
    }

    /**
     * Create the content for the email reference and data provided
     *
     * @param MailParameters $mailParameters
     * @return Message
     * @throws InvalidArgumentException
     */
    public function createMessage(MailParameters $mailParameters): Message
    {
        $to = $mailParameters->getToAddresses();
        $templateRef = $mailParameters->getTemplateRef();
        $data = $mailParameters->getData();

        $this->getLogger()->info(sprintf('Sending %s email to %s', $templateRef, implode(',', $to)));

        $template = $this->getTemplate($templateRef);
        if (is_null($template)) {
            throw new InvalidArgumentException('Missing template config for ' . $templateRef);
        }

        // Get the HTML content from the template and the data
        $emailHtml = $this->localViewRenderer->renderTemplate($template['template'], $data);

        // Construct the message to send
        $message = new Message();

        foreach ($to as $toEmails) {
            $message->addTo($toEmails);
        }

        // TODO put the from addresses into MailParameters so this class doesn't need
        // access to config at all; this should be decided by the service method
        // rather than globally here
        $emailConfig = $this->config['email'];

        // Set the FROM address - override where necessary for certain email types
        $from = $emailConfig['sender']['default']['address'];
        $fromName = $emailConfig['sender']['default']['name'];

        if ($templateRef == AbstractEmailService::EMAIL_FEEDBACK) {
            $from = $emailConfig['sender']['feedback']['address'];
            $fromName = $emailConfig['sender']['feedback']['name'];
        }

        // If config is broken, may throw InvalidArgumentException
        $message->addFrom($from, $fromName);

        // Add the categories for this message
        foreach ($template['categories'] as $category) {
            $message->addCategory($category);
        }

        // Get the subject from the template content and set it in the message
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
