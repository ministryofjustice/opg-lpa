<?php

namespace ApplicationTest\Model\Rest\Lock;

use Application\Model\Rest\Lock\Resource as LockResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return LockResource
     */
    public function build()
    {
        /** @var LockResource $resource */
        $resource = parent::buildMocks(LockResource::class);
        return $resource;
    }
}