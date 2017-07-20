<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\DynamoCronLock;

class TestableDynamoCronLock extends DynamoCronLock
{
    public $mockClient = null;

    protected function getClient()
    {
        return $this->mockClient ?: parent::getClient();
    }
}