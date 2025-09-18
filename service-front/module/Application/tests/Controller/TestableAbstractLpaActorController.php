<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AbstractLpaActorController;
use Application\Form\Lpa\AbstractActorForm;
use MakeShared\DataModel\AbstractData;

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

    public function testUpdateCorrespondentData(AbstractData $actor, bool $isDelete = false)
    {
        return parent::updateCorrespondentData($actor, $isDelete);
    }
}
