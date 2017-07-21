<?php

namespace ApplicationTest\Model\Rest\AttorneyDecisionsPrimary;

use Application\Model\Rest\AttorneyDecisionsPrimary\Resource as AttorneyDecisionsPrimaryResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @return AttorneyDecisionsPrimaryResource
     */
    public function build()
    {
        $resource = new AttorneyDecisionsPrimaryResource();
        parent::buildMocks($resource);
        return $resource;
    }
}