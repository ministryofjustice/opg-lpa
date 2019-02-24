<?php

namespace ApplicationTest\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\View\Helper\SystemMessage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SystemMessageTest extends MockeryTestCase
{
    public function testInvoke():void {

        //$container = Mockery::mock(ContainerInterface::class);

        $cache = Mockery::mock(DynamoDbKeyValueStore::class);
        $cache->shouldReceive('getItem')->withArgs(['system-message'])->once()->andReturn("test message  ");

        $systemMessage = new SystemMessage($cache);
        $systemMessage();
    }

}