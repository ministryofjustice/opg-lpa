<?php

namespace Application\Model\DataAccess\Mongo\Collection;

trait ApiStatsLpasCollectionTrait
{
    /**
     * @var ApiStatsLpasCollection
     */
    private $apiStatsLpasCollection;

    /**
     * @param ApiStatsLpasCollection $apiStatsLpasCollection
     * @return $this
     */
    public function setApiStatsLpasCollection(ApiStatsLpasCollection $apiStatsLpasCollection)
    {
        $this->apiStatsLpasCollection = $apiStatsLpasCollection;

        return $this;
    }
}
