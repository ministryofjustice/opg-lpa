<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\ReplacementAttorneyController;

class TestableReplacementAttorneyController extends ReplacementAttorneyController
{
    public function testGetActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        return parent::getActorReuseDetails($includeTrusts, $forCorrespondent);
    }
}
