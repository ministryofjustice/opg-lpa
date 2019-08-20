<?php

namespace ApplicationTest\Controller;

use Application\Controller\AbstractLpaController;

class TestableAbstractLpaController extends AbstractLpaController
{
    public $injectedFlowChecker;

    public function getFlowChecker()
    {
        return $this->injectedFlowChecker ?: parent::getFlowChecker();
    }

    public function testMoveToNextRoute()
    {
        return parent::moveToNextRoute();
    }
}
