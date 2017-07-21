<?php

namespace ApplicationTest\Model\Rest\Correspondent;

use Application\Model\Rest\Correspondent\Resource as CorrespondentResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return CorrespondentResource
     */
    public function build()
    {
        $resource = new CorrespondentResource();
        parent::buildMocks($resource);
        return $resource;
    }
}