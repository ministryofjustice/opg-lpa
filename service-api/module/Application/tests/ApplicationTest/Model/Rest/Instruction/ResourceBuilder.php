<?php

namespace ApplicationTest\Model\Rest\Instruction;

use Application\Model\Rest\Instruction\Resource as InstructionResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return InstructionResource
     */
    public function build()
    {
        $resource = new InstructionResource();
        parent::buildMocks($resource);
        return $resource;
    }
}