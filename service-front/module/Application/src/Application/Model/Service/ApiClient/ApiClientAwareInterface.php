<?php

namespace Application\Model\Service\ApiClient;

interface ApiClientAwareInterface
{
    /**
     * @param Client $apiClient
     * @return $this
     */
    public function setApiClient(Client $apiClient);
}
