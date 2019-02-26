<?php

namespace ApplicationTest\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\View\Helper\SystemMessageFactory;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SystemMessageFactoryTest extends MockeryTestCase
{
    public function testInvoke():void
    {
        $container = Mockery::mock(ContainerInterface::class);

        $cache = Mockery::mock(DynamoDbKeyValueStore::class);
        $container->shouldReceive('get')->withArgs(['Cache'])->once()->andReturn($cache);

        $systemMessageFactory = new SystemMessageFactory();
        $systemMessageFactory($container, "", null);
    }
}
