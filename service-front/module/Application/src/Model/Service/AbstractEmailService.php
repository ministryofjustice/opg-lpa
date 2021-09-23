<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Laminas\View\HelperPluginManager;

abstract class AbstractEmailService extends AbstractService
{
    /**
     * @var MailTransportInterface
     */
    private $mailTransport;

    /**
     * This is used to get at view helpers such as the URL renderer.
     *
     * @var HelperPluginManager
     */
    private $helperPluginManager;

    /**
     * Email template references. Individual MailTransportInterface
     * implementations may map these to different rendering mechanisms (e.g. Twig
     * to create HTML bodies for SendGrid) or 3rd party identifiers (e.g. Notify
     * template IDs)
     */
    public const EMAIL_ACCOUNT_ACTIVATE                = 'email-account-activate';
    public const EMAIL_FEEDBACK                        = 'email-feedback';
    public const EMAIL_LPA_REGISTRATION                = 'email-lpa-registration';
    public const EMAIL_LPA_REGISTRATION_WITH_PAYMENT   = 'email-lpa-registration-with-payment';
    public const EMAIL_NEW_EMAIL_ADDRESS_NOTIFY        = 'email-new-email-address-notify';
    public const EMAIL_NEW_EMAIL_ADDRESS_VERIFY        = 'email-new-email-address-verify';
    public const EMAIL_PASSWORD_CHANGED                = 'email-password-changed';
    public const EMAIL_PASSWORD_RESET                  = 'email-password-reset';
    public const EMAIL_PASSWORD_RESET_NO_ACCOUNT       = 'email-password-reset-no-account';
    public const EMAIL_ACCOUNT_DUPLICATION_WARNING     = 'email-account-duplication-warning';
    public const EMAIL_SENDGRID_BOUNCE                 = 'email-sendgrid-bounce';

    /**
     * AbstractEmailService constructor.
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param MailTransportInterface $mailTransport
     * @param HelperPluginManager $helperPluginManager
     */
    public function __construct(
        AuthenticationService $authenticationService,
        array $config,
        MailTransportInterface $mailTransport,
        HelperPluginManager $helperPluginManager
    ) {
        parent::__construct($authenticationService, $config);
        $this->mailTransport = $mailTransport;
        $this->helperPluginManager = $helperPluginManager;
    }

    /**
     * @return MailTransportInterface
     */
    public function getMailTransport(): MailTransportInterface
    {
        return $this->mailTransport;
    }

    /**
     * Call the URL view helper
     *
     * @param string $name
     * @param array $params
     * @param array $options
     * @return string
     */
    public function url($name = null, $params = [], $options = [])
    {
        $urlHelper = $this->helperPluginManager->get('url');
        return $urlHelper($name, $params, $options);
    }

    /**
     * Format an LPA ID using the helper
     * @param string $lpaId
     * @return string Formatted LPA ID
     */
    public function formatLpaId($lpaId)
    {
        $formatLpaIdHelper = $this->helperPluginManager->get('formatLpaId');
        return $formatLpaIdHelper($lpaId);
    }

    /**
     * Format a money string with decimal points etc.
     * @param string $money
     * @return string Formatted money
     */
    public function moneyFormat($money)
    {
        $moneyFormatHelper = $this->helperPluginManager->get('moneyFormat');
        return $moneyFormatHelper($money);
    }
}
