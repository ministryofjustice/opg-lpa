<?php

namespace ApplicationTest\Model\Rest\Preference;

use Application\Model\Rest\Preference\Resource as PreferenceResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return PreferenceResource
     */
    public function build()
    {
        $resource = new PreferenceResource();
        parent::buildMocks($resource);
        return $resource;
    }
}