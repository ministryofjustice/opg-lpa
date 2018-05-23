<?php

namespace ApplicationTest\Model\Service\Applications;

use Application\Model\Service\Applications\Service;
use Opg\Lpa\DataModel\Lpa\Lpa;

class TestableService extends Service
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