<?php

namespace ApplicationTest\Model\Rest\Users;

use Application\DataAccess\Mongo\CollectionFactory;
use Application\Model\Rest\Users\Resource as UsersResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $userCollection = null;

    private $userDal;

    /**
     * @return UsersResource
     */
    public function build()
    {
        /** @var UsersResource $resource */
        $resource = parent::buildMocks(UsersResource::class);

        $resource->setUserDal($this->userDal);

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

    public function withUserDal($userDal)
    {
        $this->userDal = $userDal;
        return $this;
    }
}