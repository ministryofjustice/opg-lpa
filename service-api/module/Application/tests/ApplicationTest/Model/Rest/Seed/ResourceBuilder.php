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
        /** @var SeedResource $resource */
        $resource = parent::buildMocks(SeedResource::class);

        if ($this->applicationsResource !== null) {
            $resource->setApplicationsResource($this->applicationsResource);
        }

        return $resource;
    }
}