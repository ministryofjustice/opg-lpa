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
    public function getAuthStats()
    {
        return $this->parseResponse($this->apiClient->httpGet('/v1/stats'));
    }

    /**
     * @return bool|mixed
     */
    public function getApiStats()
    {
        return $this->parseResponse($this->apiClient->httpGet('/v2/stats/all'));
    }

    /**
     * @param ResponseInterface $response
     * @return bool|mixed
     */
    private function parseResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }
}
