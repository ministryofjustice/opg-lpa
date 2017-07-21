<?php

namespace ApplicationTest\Model\Rest\Users;

use Application\Model\Rest\Users\Resource as UsersResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $userCollection = null;

    /**
     * @return UsersResource
     */
    public function build()
    {
        $resource = new UsersResource();
        parent::buildMocks($resource);

        if ($this->userCollection !== null) {
            $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-user')->andReturn($this->userCollection);
        }

        return $resource;
    }

    /**
     * @param $userCollection
     * @return $this
     */
    public function withUserCollection($userCollection)
    {
        $this->userCollection = $userCollection;
        return $this;
    }
}