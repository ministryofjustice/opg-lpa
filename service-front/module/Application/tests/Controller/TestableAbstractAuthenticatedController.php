<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AbstractAuthenticatedController;

class TestableAbstractAuthenticatedController extends AbstractAuthenticatedController
{
    public function testResetSessionCloneData(string $seedId)
    {
        return parent::resetSessionCloneData($seedId);
    }
}
