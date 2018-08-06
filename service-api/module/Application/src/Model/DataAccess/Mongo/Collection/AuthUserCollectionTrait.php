<?php

namespace Application\Model\DataAccess\Mongo\Collection;

trait AuthUserCollectionTrait
{
    /**
     * @var AuthUserCollection
     */
    private $authUserCollection;

    /**
     * @param AuthUserCollection $authUserCollection
     * @return $this
     */
    public function setAuthUserCollection(AuthUserCollection $authUserCollection)
    {
        $this->authUserCollection = $authUserCollection;

        return $this;
    }
}
