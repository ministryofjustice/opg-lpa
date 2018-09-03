<?php

namespace Application\Model\DataAccess\Mongo\Collection;

/*
 * We need to keep this as it's used by AuthUserCollection
 */

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
