<?php

namespace ApplicationTest\Model\Rest\WhoIsRegistering;

use Application\Model\Rest\WhoIsRegistering\Resource as WhoIsRegisteringResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return WhoIsRegisteringResource
     */
    public function build()
    {
        $resource = new WhoIsRegisteringResource();
        parent::buildMocks($resource);
        return $resource;
    }
}