<?php

namespace ApplicationTest\Controller;

use Application\Controller\AbstractAuthenticatedController;

class TestableAbstractAuthenticatedController extends AbstractAuthenticatedController
{
    public function testResetSessionCloneData($seedId)
    {
        return parent::resetSessionCloneData($seedId);
    }
}
