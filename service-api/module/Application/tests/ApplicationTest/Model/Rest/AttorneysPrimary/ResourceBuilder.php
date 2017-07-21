<?php

namespace ApplicationTest\Model\Rest\AttorneysPrimary;

use Application\Model\Rest\AttorneysPrimary\Resource as AttorneysPrimaryResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return AttorneysPrimaryResource
     */
    public function build()
    {
        $resource = new AttorneysPrimaryResource();
        parent::buildMocks($resource);
        return $resource;
    }
}