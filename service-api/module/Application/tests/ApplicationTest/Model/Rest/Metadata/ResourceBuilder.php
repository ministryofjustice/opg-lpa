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
        /** @var MetadataResource $resource */
        $resource = parent::buildMocks(MetadataResource::class);
        return $resource;
    }
}