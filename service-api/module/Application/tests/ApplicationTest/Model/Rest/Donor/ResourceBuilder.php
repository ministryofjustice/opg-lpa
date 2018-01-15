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
        /** @var DonorResource $resource */
        $resource = parent::buildMocks(DonorResource::class);
        return $resource;
    }
}