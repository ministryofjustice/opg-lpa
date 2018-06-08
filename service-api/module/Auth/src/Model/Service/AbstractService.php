<?php

namespace Auth\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;

abstract class AbstractService
{
    /**
     * @var AuthUserCollection
     */
    private $authUserCollection;

    public function __construct(AuthUserCollection $authUserCollection)
    {
        $this->authUserCollection = $authUserCollection;
    }

    protected function getAuthUserCollection(): AuthUserCollection
    {
        return $this->authUserCollection;
    }
}
