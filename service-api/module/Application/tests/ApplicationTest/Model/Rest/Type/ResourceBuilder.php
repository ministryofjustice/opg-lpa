<?php

namespace ApplicationTest\Model\Rest\Type;

use Application\Model\Rest\Type\Resource as TypeResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return TypeResource
     */
    public function build()
    {
        /** @var TypeResource $resource */
        $resource = parent::buildMocks(TypeResource::class);
        return $resource;
    }
}