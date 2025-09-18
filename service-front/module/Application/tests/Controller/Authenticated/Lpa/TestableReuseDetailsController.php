<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReuseDetailsController;

class TestableReuseDetailsController extends ReuseDetailsController
{
    public function testGetActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        return parent::getActorReuseDetails($includeTrusts, $forCorrespondent);
    }
}
