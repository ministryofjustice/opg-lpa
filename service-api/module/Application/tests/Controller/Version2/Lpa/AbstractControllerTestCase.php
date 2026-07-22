<?php

namespace ApplicationTest\Controller\Version2\Lpa;

use Application\Controller\Version2\Lpa\AbstractLpaController;
use Application\Library\Authentication\Identity\User;
use Application\Model\Service\EntityInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Header\GenericHeader;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Stdlib\Parameters;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

abstract class AbstractControllerTestCase extends MockeryTestCase
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
     * @var AuthenticationService|MockInterface
     */
    protected $authenticationService;

    /**
     * @var User|MockInterface
     */
    protected $identity;

    /**
     * @var RouteMatch|MockInterface
     */
    protected $routeMatch;

    /**
     * @var MvcEvent|MockInterface
     */
    protected $mvcEvent;

    /**
     * @var Request|MockInterface
     */
    protected $request;

    public function setUp(): void
    {
        $this->userId = 12345;
        $this->lpaId = 98765;

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('stopped');

        $this->eventManager = Mockery::mock(EventManager::class);
        $this->eventManager->shouldReceive('setIdentifiers');
        $this->eventManager->shouldReceive('attach');
        $this->eventManager->shouldReceive('triggerEventUntil')->andReturn($response);

        $this->identity = Mockery::mock(User::class);
        $this->identity->shouldReceive('getId')->andReturn(99999);
        $this->identity->shouldReceive('id')->andReturn(99999);
        $this->identity->shouldReceive('hasRole')->withArgs(['admin'])->andReturn(true);
        $this->identity->shouldReceive('email')->andReturn('identity@email.address');

        $authenticationService = Mockery::mock(AuthenticationService::class);
        $authenticationService->shouldReceive('getIdentity')->andReturn($this->identity);

        $this->routeMatch = Mockery::mock(RouteMatch::class);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['userId'])->andReturn($this->userId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpaId'])->andReturn($this->lpaId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['lpaId', false])->andReturn($this->lpaId);
        $this->routeMatch->shouldReceive('getParam')->withArgs(['action', false])->andReturn(false);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('dummy');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('setStatusCode');

        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->mvcEvent->shouldReceive('getRouteMatch')->andReturn($this->routeMatch);
        $this->mvcEvent->shouldReceive('getRequest')->andReturn($request);
        $this->mvcEvent->shouldReceive('getResponse')->andReturn($response);

        $this->authenticationService = $authenticationService;
    }

    /**
     * Helper to create a mock for the EntityInterface
     * @param null $array
     * @return EntityInterface|MockInterface
     */
    protected function createEntity($array = null): EntityInterface
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
        $this->identity->mockery_findExpectation('hasRole', ['admin'])->andReturn($authorised);
    }

    protected function callDispatch(AbstractLpaController $abstractController, array $parameters = [])
    {
        $abstractController->setEventManager($this->eventManager);

        $params = new Parameters($parameters);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getQuery')->andReturn($params);
        $request->shouldReceive('getHeader')
            ->with('X-Trace-Id')
            ->andReturn(new GenericHeader('X-Trace-Id', 'trace-id-123'));

        $abstractController->dispatch($request);
    }

    protected function callOnDispatch(AbstractLpaController $abstractController)
    {
        return $abstractController->onDispatch($this->mvcEvent);
    }
}
