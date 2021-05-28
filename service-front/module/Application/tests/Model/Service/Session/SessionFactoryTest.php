<?php

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionFactory;
use Application\Model\Service\Session\SessionManager;
use ApplicationTest\Model\Service\ServiceTestHelper;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\Session\Exception\RuntimeException;

class SessionFactoryTest extends MockeryTestCase
{
    /**
     * Because SessionFactory messes with ini_set, we have to run this test
     * in its own process.
     *
     * @runInSeparateProcess
     */
    public function testSessionFactory() : void
    {
        ServiceTestHelper::disableRedisSaveHandler();

        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn([
                'session' => [
                    'native_settings' => [
                        'name' => 'foo'
                    ]
                ]
            ]);

        $factory = new SessionFactory();
        $result = $factory($container, null, null);

        $this->assertInstanceOf(SessionManager::class, $result);
        $this->assertEquals(ini_get('session.name'), 'foo');
    }

    public function testSessionFactoryNoSessionConfig() : void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session configuration setting not found');

        $factory = new SessionFactory();
        $result = $factory($container, null, null);
    }
}
