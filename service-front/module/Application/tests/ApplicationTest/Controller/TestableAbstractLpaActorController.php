<?php

namespace ApplicationTest\Controller;

use Application\Controller\AbstractLpaActorController;

class TestableAbstractLpaActorController extends AbstractLpaActorController
{
    public function testGetActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        return parent::getActorReuseDetails($includeTrusts, $forCorrespondent);
    }
}