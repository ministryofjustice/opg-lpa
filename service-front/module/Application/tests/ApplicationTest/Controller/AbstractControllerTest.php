<?php

namespace ApplicationTest\Controller;

use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;
use PHPUnit_Framework_Error_Deprecated;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockInterface|ServiceLocatorInterface
     */
    protected $serviceLocator;
    /**
     * @var MockInterface|Logger
     */
    protected $logger;

    public function setUp()
    {
        //Required to suppress the deprecated error received when calling getServiceLocator()
        //Calling and using the service locator directly in code could be considered a IoC/DI anti pattern
        //Ideally we would be injecting dependencies via constructor args or setters via the IoC container
        //This work will be carried out as part of the upgrade to Zend 3
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $this->serviceLocator = Mockery::mock(ServiceLocatorInterface::class);

        $this->logger = Mockery::mock(Logger::class);
        $this->logger->shouldReceive('info');
        $this->serviceLocator->shouldReceive('get')->with('Logger')->andReturn($this->logger);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}