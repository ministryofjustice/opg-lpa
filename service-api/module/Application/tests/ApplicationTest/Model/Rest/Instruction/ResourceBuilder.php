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
        /** @var InstructionResource $resource */
        $resource = parent::buildMocks(InstructionResource::class);
        return $resource;
    }
}