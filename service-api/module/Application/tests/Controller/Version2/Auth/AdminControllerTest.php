<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\AdminController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Users\Service as UsersService;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\ResponseCollection;
use Laminas\Http\Header\ContentType;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\PluginManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AdminControllerTest extends MockeryTestCase
{
    /**
     * @var MockInterface|UsersService
     */
    private $usersService;

    /**
     * @var MockInterface|ApplicationsService
     */
    private $applicationsService;

    /**
     * @var MockInterface|PluginManager
     */
    private $pluginManager;

    /**
     * @var MockInterface|Params
     */
    private $params;

    /**
     * @var MockInterface|Request
     */
    private $request;

    /**
     * @var MockInterface|EventManager
     */
    private $eventManager;

    public function setUp(): void
    {
        $this->usersService = Mockery::mock(UsersService::class);
        $this->applicationsService = Mockery::mock(ApplicationsService::class);

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

        $eventManager = Mockery::mock(EventManager::class);
        $eventManager->shouldReceive('setIdentifiers');
        $eventManager->shouldReceive('attach');

        $responseCollection = Mockery::mock(ResponseCollection::class);
        $responseCollection->shouldReceive('stopped')
            ->andReturn(false);

        $eventManager->shouldReceive('triggerEventUntil')
            ->andReturn($responseCollection);

        $this->eventManager = $eventManager;

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
        $this->request->shouldReceive('getContent')
            ->andReturn('{}');
    }

    private function getController(): AdminController
    {
        $controller = new AdminController(
            $this->usersService,
            $this->applicationsService
        );

        $controller->setPluginManager($this->pluginManager);
        $controller->setEventManager($this->eventManager);

        $controller->dispatch($this->request);

        return $controller;
    }

    public function testSearchUsersAction()
    {
        $emailAddress = 'user@name.com';

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'email' => $emailAddress,
            ])
            ->once();

        $userSearchReturnData = [
            'userId' => 'ertyu34565456ytyg',
            'email'  => $emailAddress,
        ];

        $this->usersService->shouldReceive('searchByUsername')
            ->with($emailAddress)
            ->andReturn($userSearchReturnData)
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->searchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testSearchUsersActionFailed()
    {
        $emailAddress = 'user@name.com';

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'email' => $emailAddress,
            ])
            ->once();

        $this->usersService->shouldReceive('searchByUsername')
            ->with($emailAddress)
            ->andReturnFalse()
            ->once();

        $controller = $this->getController();

        /** @var ApiProblem $result */
        $result = $controller->searchUsersAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('No user found with supplied email address', $data['detail']);
    }

    public function testSearchUsersActionByAReference()
    {
        $aReference = 'A-99998888882';

        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'aReference' => $aReference,
            ])
            ->once();

        $userSearchReturnData = [
            'userId'   => 'abc123def456',
            'isActive' => true,
        ];

        $this->usersService->shouldReceive('searchByAReference')
            ->with($aReference)
            ->andReturn($userSearchReturnData)
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->searchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testSearchUsersActionByAReferenceNotFound()
    {
        $aReference = 'A-00000000000';

        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'aReference' => $aReference,
            ])
            ->once();

        $this->usersService->shouldReceive('searchByAReference')
            ->with($aReference)
            ->andReturnFalse()
            ->once();

        $controller = $this->getController();

        /** @var ApiProblem $result */
        $result = $controller->searchUsersAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('No user found with supplied A Reference', $data['detail']);
    }

    public function testMatchUsersAction()
    {
        $query = 'horace';

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->with('query')
            ->andReturn($query)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('limit', 10)
            ->andReturn(10)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('offset', 0)
            ->andReturn(0)
            ->once();

        $userMatchReturnData = [
            [
                'email' => 'horace@foo.com',
                'user'  => 'ertyu34565456ytyg',
            ],
            [
                'email' => 'foo@horace.com',
                'user'  => 'ddasdwrq2524525',
            ],
        ];

        $this->usersService->shouldReceive('matchUsers')
            ->with($query, ['offset' => 0, 'limit' => 10])
            ->andReturn($userMatchReturnData)
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        // NB query parameter comes from query string via the params plugin
        // (see setUp above)
        $result = $controller->matchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testMatchUsersActionEmptyResultset()
    {
        $query = 'phoebe';
        $offset = 10;
        $limit = 5;

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->with('query')
            ->andReturn($query)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('limit', 10)
            ->andReturn($limit)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('offset', 0)
            ->andReturn($offset)
            ->once();

        $userMatchReturnData = [];

        $expectedOptions = [
            'offset' => $offset,
            'limit'  => $limit,
        ];

        $this->usersService->shouldReceive('matchUsers')
            ->with($query, $expectedOptions)
            ->andReturn($userMatchReturnData)
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        // NB query parameter comes from query string via the params plugin
        // (see setUp above)
        $result = $controller->matchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
    }
}
