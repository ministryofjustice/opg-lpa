<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\DynamoCronLock;

class TestableDynamoCronLock extends DynamoCronLock
{
    public $mockClient = null;

    public function testGetClient()
    {
        return $this->getClient();
    }

    protected function getClient()
    {
        return $this->mockClient ?: parent::getClient();
    }
}