<?php

namespace Application\Model\DataAccess\Mongo\Collection;

trait AuthLogCollectionTrait
{
    /**
     * @var AuthLogCollection
     */
    private $authLogCollection;

    /**
     * @param AuthLogCollection $authLogCollection
     * @return $this
     */
    public function setAuthLogCollection(AuthLogCollection $authLogCollection)
    {
        $this->authLogCollection = $authLogCollection;

        return $this;
    }
}
