<?php

namespace ApplicationTest\Model\Rest\WhoAreYou;

use Application\Model\Rest\WhoAreYou\Resource as WhoAreYouResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @return WhoAreYouResource
     */
    public function build()
    {
        /** @var WhoAreYouResource $resource */
        $resource = parent::buildMocks(WhoAreYouResource::class, true, $this->statsWhoCollection);
        return $resource;
    }
}