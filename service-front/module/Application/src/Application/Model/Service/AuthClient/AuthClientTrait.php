<?php

namespace Application\Model\Service\AuthClient;

trait AuthClientTrait
{
    /**
     * @var Client
     */
    private $authClient;

    /**
     * @param Client $authClient
     * @return $this
     */
    public function setAuthClient(Client $authClient)
    {
        $this->authClient = $authClient;

        return $this;
    }
}
