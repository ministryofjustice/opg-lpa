<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\SharedSpaceController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\ResponseCollection;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;

class SharedSpaceControllerTest extends MockeryTestCase
{
    private MockInterface|SharedSpaceService $sharedSpaceService;
    private MockInterface|ApplicationsService $applicationsService;
    private MockInterface|AuthenticationService $authenticationService;
    private SharedSpaceController $controller;

    public function setUp(): void
    {
        $this->sharedSpaceService = Mockery::mock(SharedSpaceService::class);
        $this->applicationsService = Mockery::mock(ApplicationsService::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $eventManager = Mockery::mock(EventManager::class);
        $eventManager->shouldReceive('setIdentifiers');
        $eventManager->shouldReceive('attach');
        $eventManager->shouldReceive('triggerEventUntil')
            ->andReturn(new ResponseCollection());

        $this->controller = new SharedSpaceController(
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->applicationsService,
        );

        $this->controller->setEventManager($eventManager);

        parent::setUp();
    }

    private function makeRequest(array|bool $tokenValue, ?array $requestContentForJson = null): void
    {
        $token = 'tokenval123';

        $this->authenticationService->shouldReceive('withToken')
            ->with($token, false)
            ->andReturn($tokenValue)
            ->once();

        $request = Mockery::mock(Request::class);

        $request->shouldReceive('getHeader')
            ->with('Token')
            ->andReturn(new GenericHeader('Token', $token))
            ->once();

        if ($requestContentForJson !== null) {
            $request->shouldReceive('getHeaders')
                ->andReturn(Headers::fromString('Content-Type: application/json;'));
            $request->shouldReceive('getContent')
                ->andReturn((empty($requestContentForJson) ? '{}' : json_encode($requestContentForJson)));
        }

        $this->controller->dispatch($request);
    }

    private function withParams(): MockInterface|Params
    {
        $params = Mockery::mock(Params::class);
        $params->shouldReceive('__invoke')
            ->andReturn($params);

        $pluginManager = Mockery::mock(PluginManager::class);
        $pluginManager->shouldReceive('setController');
        $pluginManager->shouldReceive('get')
            ->withArgs(['params', null])
            ->andReturn($params);

        $this->controller->setPluginManager($pluginManager);

        return $params;
    }

    public function testCreateActionSuccess()
    {
        $userId = 'user1';

        $this->sharedSpaceService->shouldReceive('create')
            ->with('My Space', $userId)
            ->andReturn([
                'sharedSpaceId' => 'shared-space-1',
                'name'          => 'My Space',
                'lpasMoved'     => 2,
            ])
            ->once();

        $this->makeRequest(['userId' => $userId], ['name' => 'My Space']);
        $result = $this->controller->createAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testCreateActionMissingName()
    {
        $userId = 'user1';

        $this->makeRequest(['userId' => $userId], []);
        $result = $this->controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
    }

    public function testCreateActionAlreadyInSharedSpace()
    {
        $userId = 'user1';

        $this->sharedSpaceService->shouldReceive('create')
            ->with('My Space', $userId)
            ->andThrow(new UserAlreadyInSharedSpaceException())
            ->once();

        $this->makeRequest(['userId' => $userId], ['name' => 'My Space']);
        $result = $this->controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
    }

    public function testCreateActionUnexpectedError()
    {
        $userId = 'user1';


        $this->sharedSpaceService->shouldReceive('create')
            ->with('My Space', $userId)
            ->andThrow(new RuntimeException('boom'))
            ->once();

        $this->makeRequest(['userId' => $userId], ['name' => 'My Space']);
        $result = $this->controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(500, $result->toArray()['status']);
    }

    public function testLpasActionSuccess()
    {
        $userId = 'user1';
        $sharedSpaceId = 'shared-space-1';
        $lpa = FixturesData::getHwLpa();

        $this->withParams()->shouldReceive('fromQuery')
            ->andReturn(['page' => 1, 'perPage' => 50]);

        $paginator = new Paginator(new ArrayAdapter([$lpa]));

        $this->applicationsService->shouldReceive('fetchAllForSharedSpace')
            ->with($sharedSpaceId, [])
            ->andReturn($paginator)
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->lpasAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);

        $this->assertEquals(1, $body['total']);
        $this->assertCount(1, $body['applications']);
    }

    public function testLpasActionDeniedWhenNotInSharedSpace()
    {
        $userId = 'user1';

        $this->applicationsService->shouldNotReceive('fetchAllForSharedSpace');

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => null]);
        $result = $this->controller->lpasAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testLpasActionInvalidToken()
    {
        $this->applicationsService->shouldNotReceive('fetchAllForSharedSpace');

        $this->makeRequest(false);
        $result = $this->controller->lpasAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }

    public function testMembersAction()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('getMembers')
            ->with($sharedSpaceId)
            ->andReturn(['a' => 'b']);

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->membersAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['members' => ['a' => 'b']], $body);
    }

    public function testMembersActionWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->membersAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testMembersActionWhenInvalidToken()
    {
        $this->makeRequest(false);
        $result = $this->controller->membersAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }
}
