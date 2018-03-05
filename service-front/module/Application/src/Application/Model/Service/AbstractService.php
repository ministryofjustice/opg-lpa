<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;

abstract class AbstractService
{
    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var array
     */
    private $config;

    /**
     * AbstractService constructor.
     * @param LpaApplicationService $lpaApplicationService
     * @param AuthenticationService $authenticationService
     * @param array $config
     */
    public function __construct(
        LpaApplicationService $lpaApplicationService,
        AuthenticationService $authenticationService,
        array $config
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->authenticationService = $authenticationService;
        $this->config = $config;
    }

    /**
     * @return LpaApplicationService
     */
    public function getLpaApplicationService(): LpaApplicationService
    {
        return $this->lpaApplicationService;
    }

    /**
     * @return AuthenticationService
     */
    public function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}