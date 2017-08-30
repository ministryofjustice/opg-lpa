<?php

namespace ApplicationTest\Controller;

use Application\Controller\AbstractLpaActorController;
use Application\Form\Lpa\AbstractActorForm;

class TestableAbstractLpaActorController extends AbstractLpaActorController
{
    public function testReuseActorDetails(AbstractActorForm $actorForm)
    {
        return parent::reuseActorDetails($actorForm);
    }

    public function testGetActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        return parent::getActorReuseDetails($includeTrusts, $forCorrespondent);
    }
}