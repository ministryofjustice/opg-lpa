<?php

namespace ApplicationTest\Model\Rest\AttorneysReplacement;

use Application\Model\Rest\AttorneysReplacement\Resource as AttorneysReplacementResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return AttorneysReplacementResource
     */
    public function build()
    {
        /** @var AttorneysReplacementResource $resource */
        $resource = parent::buildMocks(AttorneysReplacementResource::class);
        return $resource;
    }
}