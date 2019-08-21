<?php

namespace Application\Model\Service\Stats;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;

class Stats extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * @return bool|mixed
     */
    public function getApiStats()
    {
        try {
            return $this->apiClient->httpGet('/stats/all');
        } catch (ApiException $ex) {}

        return false;
    }
}
