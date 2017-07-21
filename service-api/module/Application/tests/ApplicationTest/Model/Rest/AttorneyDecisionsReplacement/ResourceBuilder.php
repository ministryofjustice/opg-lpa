<?php

namespace ApplicationTest\Model\Rest\AttorneyDecisionsReplacement;

use Application\Model\Rest\AttorneyDecisionsReplacement\Resource as AttorneyDecisionsReplacementResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @return AttorneyDecisionsReplacementResource
     */
    public function build()
    {
        $resource = new AttorneyDecisionsReplacementResource();
        parent::buildMocks($resource);
        return $resource;
    }
}