<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\AdminController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
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
     * @var MockInterface|SharedSpaceService
     */
    private $sharedSpaceService;

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
        $this->sharedSpaceService = Mockery::mock(SharedSpaceService::class);

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
            $this->applicationsService,
            $this->sharedSpaceService
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

        /** @var ApiProblemResponse $result */
        $result = $controller->searchUsersAction();

        $this->assertInstanceOf(ApiProblemResponse::class, $result);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No user found with supplied email address', json_decode($result->getContent(), true)['detail']);
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

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No user found with supplied A Reference', json_decode($result->getContent(), true)['detail']);
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
            ->with('limit', 20)
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
            ->andReturn(['results' => $userMatchReturnData, 'total' => 2])
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        // NB query parameter comes from query string via the params plugin
        // (see setUp above)
        $result = $controller->matchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(
            ['results' => $userMatchReturnData, 'total' => 2],
            json_decode($result->getContent(), true)
        );
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
            ->with('limit', 20)
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
            ->andReturn(['results' => $userMatchReturnData, 'total' => 0])
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        // NB query parameter comes from query string via the params plugin
        // (see setUp above)
        $result = $controller->matchUsersAction();

        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(
            ['results' => [], 'total' => 0],
            json_decode($result->getContent(), true)
        );
    }

    public function testMatchSharedSpacesAction()
    {
        $fullOrPartialName = 'The Space';

        $this->params->shouldReceive('fromQuery')
            ->with('fullOrPartialName')
            ->andReturn($fullOrPartialName)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('limit', 20)
            ->andReturn(20)
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->with('offset', 0)
            ->andReturn(0)
            ->once();

        $sharedSpaceMatchReturnData = [
            [
                'sharedSpaceId'   => 'ss1',
                'sharedSpaceName' => 'The Space',
            ],
        ];

        $this->sharedSpaceService->shouldReceive('matchSharedSpaces')
            ->with($fullOrPartialName, ['offset' => 0, 'limit' => 20])
            ->andReturn(['results' => $sharedSpaceMatchReturnData, 'total' => 1])
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->matchSharedSpacesAction();

        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(
            ['results' => $sharedSpaceMatchReturnData, 'total' => 1],
            json_decode($result->getContent(), true)
        );
    }

    public function testSharedSpaceMembersAction()
    {
        $sharedSpaceId = 'ss1';

        $this->params->shouldReceive('fromRoute')
            ->with('sharedSpaceId')
            ->andReturn($sharedSpaceId)
            ->once();

        $this->sharedSpaceService->shouldReceive('getName')
            ->with($sharedSpaceId)
            ->andReturn('The Space')
            ->once();

        $this->params->shouldReceive('fromQuery')
            ->withNoArgs()
            ->andReturn(['membersPage' => '2', 'invitesPage' => '3'])
            ->once();

        $members = [
            ['userId' => 'u1', 'name' => 'Alice', 'email' => 'alice@example.com', 'isAdmin' => true],
            ['userId' => 'u2', 'name' => 'Bob', 'email' => 'bob@example.com', 'isAdmin' => false],
        ];

        $invites = [
            ['id' => 1, 'fullName' => 'Carol', 'email' => 'carol@example.com', 'createdAt' => '2024-01-01T00:00:00.000000+0000', 'isExpired' => false],
        ];

        $this->sharedSpaceService->shouldReceive('getMembersPaginated')
            ->with($sharedSpaceId, 2, 20)
            ->andReturn(['results' => $members, 'total' => 22])
            ->once();

        $this->sharedSpaceService->shouldReceive('getInvitesPaginated')
            ->with($sharedSpaceId, 3, 20)
            ->andReturn(['results' => $invites, 'total' => 41])
            ->once();

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->sharedSpaceMembersAction();

        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(
            [
                'sharedSpaceName' => 'The Space',
                'members' => $members,
                'membersTotal' => 22,
                'invites' => $invites,
                'invitesTotal' => 41,
            ],
            json_decode($result->getContent(), true)
        );
    }

    public function testSharedSpaceMembersActionNotFound()
    {
        $sharedSpaceId = 'unknown';

        $this->params->shouldReceive('fromRoute')
            ->with('sharedSpaceId')
            ->andReturn($sharedSpaceId)
            ->once();

        $this->sharedSpaceService->shouldReceive('getName')
            ->with($sharedSpaceId)
            ->andReturn(null)
            ->once();

        $controller = $this->getController();

        /** @var ApiProblemResponse $result */
        $result = $controller->sharedSpaceMembersAction();

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }
}
