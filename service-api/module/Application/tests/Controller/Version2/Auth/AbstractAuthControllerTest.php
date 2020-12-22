<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\AbstractAuthController;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Application\Logging\Logger;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\ResponseCollection;
use Laminas\Http\Header\ContentType;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\PluginManager;

abstract class AbstractAuthControllerTest extends MockeryTestCase
{
    /**
     * @var MockInterface|AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var MockInterface|AbstractService
     */
    protected $service;

    /**
     * @var MockInterface|Logger
     */
    protected $logger;

    /**
     * @var MockInterface|PluginManager
     */
    protected $pluginManager;

    /**
     * @var MockInterface|Params
     */
    protected $params;

    /**
     * @var MockInterface|EventManager
     */
    protected $eventManager;

    /**
     * @var MockInterface|Request
     */
    protected $request;

    /**
     * Set up the services in default configuration - these can be adapted in the subclasses before getting the controller to test
     */
    public function setUp()
    {
        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->logger = Mockery::mock(Logger::class);

        //  Mock the params plugin
        $this->params = Mockery::mock(Params::class);
        $this->params->shouldReceive('__invoke')
            ->andReturn($this->params);

        //  Mock the plugin manager and set the plugins
        $this->pluginManager = Mockery::mock(PluginManager::class);
        $this->pluginManager->shouldReceive('setController');
        $this->pluginManager->shouldReceive('get')
            ->withArgs(['params', null])
            ->andReturn($this->params);

        $this->eventManager = Mockery::mock(EventManager::class);
        $this->eventManager->shouldReceive('setIdentifiers');
        $this->eventManager->shouldReceive('attach');

        $responseCollection = Mockery::mock(ResponseCollection::class);
        $responseCollection->shouldReceive('stopped')
            ->andReturn(false);

        $this->eventManager->shouldReceive('triggerEventUntil')
            ->andReturn($responseCollection);

        //  Set up the request with the content type
        $contentType = Mockery::mock(ContentType::class);
        $contentType->shouldReceive('getFieldValue')
            ->andReturn('application/json');

        $headers = Mockery::mock(Headers::class);
        $headers->shouldReceive('get')
            ->with('content-type')
            ->andReturn($contentType);

        $this->request = Mockery::mock(Request::class);
        $this->request->shouldReceive('getHeaders')
            ->andReturn($headers);
    }

    /**
     * @param string $controllerName
     * @param array $requestContentForJson
     * @return AbstractAuthController
     */
    protected function getController(string $controllerName, array $requestContentForJson = [])
    {
        //  If request data has been passed (as an array) encode it into JSON and set in the request
        $this->request->shouldReceive('getContent')
            ->andReturn((empty($requestContentForJson) ? '{}' : json_encode($requestContentForJson)));

        /** @var AbstractAuthController $controller */
        $controller = new $controllerName($this->authenticationService, $this->service);

        $controller->setLogger($this->logger);

        $controller->setPluginManager($this->pluginManager);
        $controller->setEventManager($this->eventManager);

        $controller->dispatch($this->request);

        return $controller;
    }

    protected function setToken($token, $userId, $authSuccess = true)
    {
        $tokenHeader = Mockery::mock(HeaderInterface::class);
        $tokenHeader->shouldReceive('getFieldValue')
            ->andReturn($token)
            ->once();

        $this->request->shouldReceive('getHeader')
            ->with('Token')
            ->andReturn($tokenHeader)
            ->once();

        $this->authenticationService->shouldReceive('withToken')
            ->with($token, false)
            ->andReturn(($authSuccess ? [
                'userId' => $userId,
            ] : false))
            ->once();
    }
}
