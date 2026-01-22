<?php

namespace Application\Model\Service;

use Application\Model\Service\Authentication\AuthenticationService;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

abstract class AbstractService implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var array
     */
    private $config;

    /**
     * AbstractService constructor
     *
     * @param AuthenticationService $authenticationService
     * @param array $config
     */
    public function __construct(AuthenticationService $authenticationService, array $config)
    {
        $this->authenticationService = $authenticationService;
        $this->config = $config;
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

    /**
     * @return string
     */
    protected function getUserId()
    {
        return $this->getAuthenticationService()->getIdentity()?->id();
    }
}
