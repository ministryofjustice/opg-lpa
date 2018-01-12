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
        /** @var StatusResource $resource */
        $resource = parent::buildMocks(StatusResource::class);
        return $resource;
    }
}