<?php

namespace Application\Model\Service\ApiClient;

trait ApiClientTrait
{
    /** @var Client */
    private $apiClient;

    /**
     * @param Client $apiClient
     * @return $this
     */
    public function setApiClient(Client $apiClient)
    {
        $this->apiClient = $apiClient;

        return $this;
    }
}
