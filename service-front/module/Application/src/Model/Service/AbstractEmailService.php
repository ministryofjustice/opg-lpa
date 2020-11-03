<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Twig\Environment;

abstract class AbstractEmailService extends AbstractService
{
    /**
     * @var Environment
     */
    private $twigEmailRenderer;

    /**
     * @var MailTransport
     */
    private $mailTransport;

    /**
     * AbstractEmailService constructor.
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param Environment $twigEmailRenderer
     * @param MailTransport $mailTransport
     */
    public function __construct(
        AuthenticationService $authenticationService,
        array $config,
        Environment $twigEmailRenderer,
        MailTransport $mailTransport
    ) {
        parent::__construct($authenticationService, $config);
        $this->twigEmailRenderer = $twigEmailRenderer;
        $this->mailTransport = $mailTransport;
    }

    /**
     * @return Environment
     */
    public function getTwigEmailRenderer(): Environment
    {
        return $this->twigEmailRenderer;
    }

    /**
     * @return MailTransport
     */
    public function getMailTransport(): MailTransport
    {
        return $this->mailTransport;
    }
}
