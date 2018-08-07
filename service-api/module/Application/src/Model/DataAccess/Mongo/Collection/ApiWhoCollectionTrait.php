<?php

namespace Application\Model\DataAccess\Mongo\Collection;

trait ApiWhoCollectionTrait
{
    /**
     * @var ApiWhoCollection
     */
    private $apiWhoCollection;

    /**
     * @param ApiWhoCollection $apiWhoCollection
     * @return $this
     */
    public function setApiWhoCollection(ApiWhoCollection $apiWhoCollection)
    {
        $this->apiWhoCollection = $apiWhoCollection;

        return $this;
    }
}
