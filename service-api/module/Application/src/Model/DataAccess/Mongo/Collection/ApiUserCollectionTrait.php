<?php

namespace Application\Model\DataAccess\Mongo\Collection;

trait ApiUserCollectionTrait
{
    /**
     * @var ApiUserCollection
     */
    private $apiUserCollection;

    /**
     * @param ApiUserCollection $apiUserCollection
     * @return $this
     */
    public function setApiUserCollection(ApiUserCollection $apiUserCollection)
    {
        $this->apiUserCollection = $apiUserCollection;

        return $this;
    }
}
