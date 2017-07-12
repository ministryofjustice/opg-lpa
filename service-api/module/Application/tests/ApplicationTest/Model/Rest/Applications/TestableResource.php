<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Resource;
use Opg\Lpa\DataModel\Lpa\Lpa;

class TestableResource extends Resource
{
    /**
     * @param Lpa $lpa
     */
    public function testUpdateLpa(Lpa $lpa)
    {
        //This bypasses the checking code in patch allowing us to hit otherwise unreachable code
        parent::updateLpa($lpa);
    }
}