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
        /** @var CorrespondentResource $resource */
        $resource = parent::buildMocks(CorrespondentResource::class);
        return $resource;
    }
}