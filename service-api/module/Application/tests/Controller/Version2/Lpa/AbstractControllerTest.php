<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\AbstractLpaController;
use Application\Model\Service\EntityInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\RouteMatch;
use Zend\Stdlib\ParametersInterface;
use ZfcRbac\Service\AuthorizationService;

abstract class AbstractControllerTest extends MockeryTestCase
{
    /**
     * @var int|null
     */
    protected $userId;

    /**
     * @var int|null
     */
    protected $lpaId;

    /**
     * @var EventManager|MockInterface
     */
    protected $eventManager;

    /**
     * @var AuthorizationService|MockInterface
     */
    protected $authorizationService;

    /**
     * @var RouteMatch|MockInterface
     */
    protected $routeMatch;

    /**
     * @var MvcEvent|MockInterface
     */
    protected $mvcEvent;

    public function setUp() : void {
        $this->userId = 12345;
        $this->lpaId = 98765;

        // Create mock response for the event manager
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('stopped');

        // Create an event manager for passing into 'dispatch'
        $this->eventManager = Mockery::mock(EventManager::class);
        $this->eventManager->shouldReceive('setIdentifiers');
        $this->eventManager->shouldReceive('attach');
        $this->eventManager->shouldReceive('triggerEventUntil')->andReturn($response);

        // Create default identity to be returned by the authorisation service
        $identity = Mockery::mock(IdentityInterface::class);
        $identity->shouldReceive('getEmail')->andReturn('identity@email.address');

        // Create authorisation service mock, default to return that user is authorised
        $authorizationService = Mockery::mock(AuthorizationService::class);
        $authorizationService->shouldReceive('isGranted')->withArgs(['authenticated'])
            ->andReturn(true);
        $authorizationService->shouldReceive('isGranted')
            ->withArgs(['isAuthorizedToManageUser', $this->userId])
            ->andReturn(false);
        $authorizationService->shouldReceive('isGranted')
            ->withArgs(['admin'])
            ->andReturn(true);

        $authorizationService->shouldReceive('getIdentity')->andReturn($identity);

        // Create RouteMatch for OnDispatch MvcEvent
        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['userId'])->andReturn($this->userId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpaId'])->andReturn($this->lpaId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpaId', false])->andReturn($this->lpaId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['action', false])->andReturn(false);

        // Create Request for OnDispatch MvcEvent
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('dummy');

        // Create Response for OnDispatch MvcEvent
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('setStatusCode');

        // Create MvcEvent for OnDispatch
        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->mvcEvent->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $this->mvcEvent->shouldReceive('getRequest')->andReturn($request);
        $this->mvcEvent->shouldReceive('getResponse')->andReturn($response);

        $this->authorizationService = $authorizationService;
    }

    /**
     * Helper to create a mock for the EntityInterface
     * @param null $array
     * @return EntityInterface|MockInterface
     */
    protected function createEntity($array = null) : EntityInterface
    {
        $entity = Mockery::mock(EntityInterface::class);
        $entity->shouldReceive('toArray')->andReturn($array);

        return $entity;
    }

    /**
     * Set whether the user is authorised or not
     * @param bool $authorised
     */
    protected function setAuthorised(bool $authorised): void
    {
        //Replace the default authorised value set in setUp
        $this->authorizationService->mockery_findExpectation('isGranted', ['admin'])->andReturn($authorised);
    }

    protected function callDispatch(AbstractLpaController $abstractController, Array $parameters = [])
    {
        $abstractController->setEventManager($this->eventManager);

        $params = Mockery::mock(ParametersInterface::class);
        $params->shouldReceive('toArray')->andReturn($parameters);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getQuery')->andReturn($params);

        $abstractController->dispatch($request);

    }

    protected function callOnDispatch(AbstractLpaController $abstractController)
    {
        return $abstractController->onDispatch($this->mvcEvent);
    }

}