<?php

namespace ApplicationTest\Model\Rest\Status;

use Application\Model\Rest\Status\Resource as StatusResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return StatusResource
     */
    public function build()
    {
        $resource = new StatusResource();
        parent::buildMocks($resource);
        return $resource;
    }
}