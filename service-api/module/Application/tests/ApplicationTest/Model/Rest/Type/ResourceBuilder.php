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
        $resource = new TypeResource();
        parent::buildMocks($resource);
        return $resource;
    }
}