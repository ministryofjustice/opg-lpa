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
        /** @var PreferenceResource $resource */
        $resource = parent::buildMocks(PreferenceResource::class);
        return $resource;
    }
}