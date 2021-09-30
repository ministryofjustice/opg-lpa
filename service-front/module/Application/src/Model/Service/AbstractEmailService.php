<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransportInterface;

abstract class AbstractEmailService extends AbstractService
{
    /**
     * @var MailTransportInterface
     */
    private $mailTransport;

    /**
     * Email template references. Individual MailTransportInterface
     * implementations may map these to different rendering mechanisms (e.g. Twig
     * to create HTML bodies for SendGrid) or 3rd party identifiers (e.g. Notify
     * template IDs)
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

    /**
     * AbstractEmailService constructor.
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param MailTransportInterface $mailTransport
     */
    public function __construct(
        AuthenticationService $authenticationService,
        array $config,
        MailTransportInterface $mailTransport
    ) {
        parent::__construct($authenticationService, $config);
        $this->mailTransport = $mailTransport;
    }

    /**
     * @return MailTransportInterface
     */
    public function getMailTransport(): MailTransportInterface
    {
        return $this->mailTransport;
    }
}
