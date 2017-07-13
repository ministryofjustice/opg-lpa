<?php

namespace ApplicationTest\Model\Rest\RepeatCaseNumber;

use Application\Model\Rest\RepeatCaseNumber\Resource as RepeatCaseNumberResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return RepeatCaseNumberResource
     */
    public function build()
    {
        $resource = new RepeatCaseNumberResource();
        parent::buildMocks($resource);
        return $resource;
    }
}