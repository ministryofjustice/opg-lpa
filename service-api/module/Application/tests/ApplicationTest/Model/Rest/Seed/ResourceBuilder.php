<?php

namespace ApplicationTest\Model\Rest\Seed;

use Application\Model\Rest\Seed\Resource as SeedResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @return SeedResource
     */
    public function build()
    {
        $resource = new SeedResource();
        parent::buildMocks($resource);
        return $resource;
    }
}