<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DonorController;

class TestableDonorController extends DonorController
{
    public function testGetActorsList($actorIndexToExclude = null, $filterByActorAction = true)
    {
        return parent::getActorsList($actorIndexToExclude, $filterByActorAction);
    }
}
