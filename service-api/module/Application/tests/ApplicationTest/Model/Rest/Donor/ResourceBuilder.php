<?php

namespace ApplicationTest\Model\Rest\Donor;

use Application\Model\Rest\Donor\Resource as DonorResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return DonorResource
     */
    public function build()
    {
        $resource = new DonorResource();
        parent::buildMocks($resource);
        return $resource;
    }
}