<?php

namespace ApplicationTest\Controller;

use Application\Model\Service\Authentication\AuthenticationService;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;
use PHPUnit_Framework_Error_Deprecated;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\Mvc\Controller\PluginManager;
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
    /**
     * @var MockInterface|AuthenticationService
     */
    protected $authenticationService;
    /**
     * @var MockInterface|PluginManager
     */
    protected $pluginManager;
    /**
     * @var MockInterface|Redirect
     */
    protected $redirect;
    /**
     * @var MockInterface|Params
     */
    protected $params;
    /**
     * @var array
     */
    protected $config;

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

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->serviceLocator->shouldReceive('get')->with('AuthenticationService')->andReturn($this->authenticationService);

        $this->pluginManager = Mockery::mock(PluginManager::class);
        $this->pluginManager->shouldReceive('setController');

        $this->redirect = Mockery::mock(Redirect::class);
        $this->pluginManager->shouldReceive('get')->with('redirect', null)->andReturn($this->redirect);

        $this->params = Mockery::mock(Params::class);
        $this->params->shouldReceive('__invoke')->andReturn($this->params);
        $this->pluginManager->shouldReceive('get')->with('params', null)->andReturn($this->params);

        $this->config = [
            'session' => [
                'native_settings' => [
                    'name' => 'lpa'
                ]
            ]
        ];
        $this->serviceLocator->shouldReceive('get')->with('Config')->andReturn($this->config);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}