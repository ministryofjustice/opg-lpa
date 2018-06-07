<?php

namespace Auth\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;

abstract class AbstractService
{
    /**
     * @var AuthUserCollection
     */
    private $authUserCollection;

    /**
     * @var AuthLogCollection
     */
    private $authLogCollection;

    public function __construct(AuthUserCollection $authUserCollection, AuthLogCollection $authLogCollection)
    {
        $this->authUserCollection = $authUserCollection;
        $this->authLogCollection = $authLogCollection;
    }

    protected function getAuthUserCollection(): AuthUserCollection
    {
        return $this->authUserCollection;
    }

    protected function getLogDataSource(): AuthLogCollection
    {
        return $this->authLogCollection;
    }
}
