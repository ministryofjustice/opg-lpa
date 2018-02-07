<?php

namespace Application\Model\Service;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Mail\Transport\SendGrid;
use Twig_Environment;

abstract class AbstractEmailService extends AbstractService
{
    /**
     * @var Twig_Environment
     */
    private $twigEmailRenderer;

    /**
     * @var SendGrid
     */
    private $mailTransport;

    /**
     * AbstractEmailService constructor.
     * @param ApiClient $apiClient
     * @param LpaApplicationService $lpaApplicationService
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param Twig_Environment $twigEmailRenderer
     * @param SendGrid $mailTransport
     */
    public function __construct(
        ApiClient $apiClient,
        LpaApplicationService $lpaApplicationService,
        AuthenticationService $authenticationService,
        array $config,
        Twig_Environment $twigEmailRenderer,
        SendGrid $mailTransport
    ) {
        parent::__construct($apiClient, $lpaApplicationService, $authenticationService, $config);
        $this->twigEmailRenderer = $twigEmailRenderer;
        $this->mailTransport = $mailTransport;
    }

    /**
     * @return Twig_Environment
     */
    public function getTwigEmailRenderer(): Twig_Environment
    {
        return $this->twigEmailRenderer;
    }

    /**
     * @return SendGrid
     */
    public function getMailTransport(): SendGrid
    {
        return $this->mailTransport;
    }
}