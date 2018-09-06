<?php

namespace ApplicationTest\ControllerFactory;

use Application\Controller\Console\AccountCleanupController;
use Application\ControllerFactory\AccountCleanupControllerFactory;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
use Interop\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AccountCleanupControllerFactoryTest extends MockeryTestCase
{
    /**
     * @var AccountCleanupControllerFactory
     */
    private $factory;

    /**
     * @var MockInterface|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->factory = new AccountCleanupControllerFactory();
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $this->container->shouldReceive('get')
            ->with('DynamoCronLock')
            ->andReturn(Mockery::mock(DynamoCronLock::class))
            ->once();
        $this->container->shouldReceive('get')
            ->with(AccountCleanupService::class)
            ->andReturn(Mockery::mock(AccountCleanupService::class))
            ->once();

        $controller = $this->factory->__invoke($this->container, AccountCleanupController::class);

        $this->assertNotNull($controller);
        $this->assertInstanceOf(AccountCleanupController::class, $controller);
    }
}
