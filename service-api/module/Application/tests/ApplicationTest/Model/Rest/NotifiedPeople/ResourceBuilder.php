<?php

namespace ApplicationTest\Model\Rest\NotifiedPeople;

use Application\Model\Rest\NotifiedPeople\Resource as NotifiedPeopleResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return NotifiedPeopleResource
     */
    public function build()
    {
        $resource = new NotifiedPeopleResource();
        parent::buildMocks($resource);
        return $resource;
    }
}