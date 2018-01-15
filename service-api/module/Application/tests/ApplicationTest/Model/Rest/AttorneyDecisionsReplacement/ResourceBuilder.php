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
        /** @var AttorneyDecisionsReplacementResource $resource */
        $resource = parent::buildMocks(AttorneyDecisionsReplacementResource::class);
        return $resource;
    }
}
