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
        $resource = new AttorneysReplacementResource();
        parent::buildMocks($resource);
        return $resource;
    }
}