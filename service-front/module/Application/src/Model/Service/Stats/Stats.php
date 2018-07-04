<?php

namespace Application\Model\Service\Stats;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Psr\Http\Message\ResponseInterface;

class Stats extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * @return bool|mixed
     */
    public function getApiStats()
    {
        $response = $this->apiClient->httpGet('/stats/all');

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }
}
