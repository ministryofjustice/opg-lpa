<?php

namespace ApplicationTest\Controller;

use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManager;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;
use PHPUnit_Framework_Error_Deprecated;
use Zend\EventManager\EventManager;
use Zend\EventManager\ResponseCollection;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\Mvc\Controller\PluginManager;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Storage\StorageInterface;
use Zend\Stdlib\ArrayObject;

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
     * @var MockInterface|Url
     */
    protected $url;
    /**
     * @var MockInterface|EventManager
     */
    protected $eventManager;
    /**
     * @var MockInterface|ResponseCollection
     */
    protected $responseCollection;
    /**
     * @var array
     */
    protected $config;
    /**
     * @var MockInterface|AbstractPluginManager
     */
    protected $formElementManager;
    /**
     * @var MockInterface|StorageInterface
     */
    protected $storage;
    /**
     * @var MockInterface|SessionManager
     */
    protected $sessionManager;
    /**
     * @var MockInterface|LpaApplicationService
     */
    protected $lpaApplicationService;
    /**
     * @var MockInterface|LpaAuthAdapter
     */
    protected $authenticationAdapter;
    /**
     * @var ArrayObject
     */
    protected $userDetailsSession;
    /**
     * @var User
     */
    protected $user = null;
    /**
     * @var MockInterface|Request
     */
    protected $request;

    /**
     * @param AbstractController $controller
     */
    public function controllerSetUp($controller)
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

        $this->url = Mockery::mock(Url::class);
        $this->pluginManager->shouldReceive('get')->with('url', null)->andReturn($this->url);

        $this->eventManager = Mockery::mock(EventManager::class);
        $this->eventManager->shouldReceive('setIdentifiers');
        $this->eventManager->shouldReceive('attach');

        $this->responseCollection = Mockery::mock(ResponseCollection::class);
        $this->eventManager->shouldReceive('triggerEventUntil')->andReturn($this->responseCollection);

        $this->config = [
            'session' => [
                'native_settings' => [
                    'name' => 'lpa'
                ]
            ],
            'redirects' => [
                'index' => 'https://www.gov.uk/power-of-attorney/make-lasting-power',
                'logout' => 'https://www.gov.uk/done/lasting-power-of-attorney',
            ],
        ];
        $this->serviceLocator->shouldReceive('get')->with('Config')->andReturn($this->config);

        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);
        $this->serviceLocator->shouldReceive('get')->with('FormElementManager')->andReturn($this->formElementManager);

        $this->storage = Mockery::mock(StorageInterface::class);

        $this->sessionManager = Mockery::mock(SessionManager::class);
        $this->serviceLocator->shouldReceive('get')->with('SessionManager')->andReturn($this->sessionManager);
        $this->sessionManager->shouldReceive('getStorage')->andReturn($this->storage);

        $this->lpaApplicationService = Mockery::mock(LpaApplicationService::class);
        $this->serviceLocator->shouldReceive('get')->with('LpaApplicationService')->andReturn($this->lpaApplicationService);

        $this->authenticationAdapter = Mockery::mock(LpaAuthAdapter::class);
        $this->serviceLocator->shouldReceive('get')->with('AuthenticationAdapter')->andReturn($this->authenticationAdapter);

        $this->userDetailsSession = new ArrayObject();
        $this->userDetailsSession->user = $this->user;
        $this->serviceLocator->shouldReceive('get')->with('UserDetailsSession')->andReturn($this->userDetailsSession);

        $controller->setServiceLocator($this->serviceLocator);
        $controller->setPluginManager($this->pluginManager);
        $controller->setEventManager($this->eventManager);

        $this->request = Mockery::mock(Request::class);

        $this->responseCollection->shouldReceive('stopped')->andReturn(false);
        $controller->dispatch($this->request);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}