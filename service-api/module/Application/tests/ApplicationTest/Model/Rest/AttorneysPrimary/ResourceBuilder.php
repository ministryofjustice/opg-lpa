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
        /** @var AttorneysPrimaryResource $resource */
        $resource = parent::buildMocks(AttorneysPrimaryResource::class);
        return $resource;
    }
}
