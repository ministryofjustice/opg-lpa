<?php

namespace ApplicationTest\Model\Rest\Seed;

use Application\Model\Rest\Seed\Resource as SeedResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $applicationsResource;

    /**
     * @return SeedResource
     */
    public function build()
    {
        $resource = new SeedResource();
        parent::buildMocks($resource);

        if ($this->applicationsResource !== null) {
            $this->serviceLocatorMock->shouldReceive('get')->with('resource-applications')->andReturn($this->applicationsResource);
        }

        return $resource;
    }

    /**
     * @param $applicationsResource
     * @return $this
     */
    public function withApplicationsResource($applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
        return $this;
    }
}