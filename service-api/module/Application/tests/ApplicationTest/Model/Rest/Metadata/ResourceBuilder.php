<?php

namespace ApplicationTest\Model\Rest\Metadata;

use Application\Model\Rest\Metadata\Resource as MetadataResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @return MetadataResource
     */
    public function build()
    {
        $resource = new MetadataResource();
        parent::buildMocks($resource);
        return $resource;
    }
}