<?php

namespace Application\Model\Service\AuthClient;

interface AuthClientAwareInterface
{
    /**
     * @param Client $authClient
     * @return mixed
     */
    public function setAuthClient(Client $authClient);
}
