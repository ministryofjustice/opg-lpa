<?php

namespace Application\Model\Service;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;

abstract class AbstractService
{
    /**
     * @var ApiClient
     */
    private $apiClient;

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
     * @param ApiClient $apiClient
     * @param LpaApplicationService $lpaApplicationService
     * @param AuthenticationService $authenticationService
     * @param array $config
     */
    public function __construct(
        ApiClient $apiClient,
        LpaApplicationService $lpaApplicationService,
        AuthenticationService $authenticationService,
        array $config
    ) {
        $this->apiClient = $apiClient;
        $this->lpaApplicationService = $lpaApplicationService;
        $this->authenticationService = $authenticationService;
        $this->config = $config;
    }

    /**
     * @return ApiClient
     */
    protected function getApiClient(): ApiClient
    {
        return $this->apiClient;
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