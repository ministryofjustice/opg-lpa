<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\SharedSpaceController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;
use RuntimeException;

class SharedSpaceControllerTest extends AbstractAuthControllerTestCase
{
    private ApplicationsService|Mockery\MockInterface $applicationsService;

    public function setUp(): void
    {
        $this->service = Mockery::mock(SharedSpaceService::class);
        $this->applicationsService = Mockery::mock(ApplicationsService::class);

        parent::setUp();
    }

    private function getSharedSpaceController(array $requestContentForJson = []): SharedSpaceController
    {
        /** @var SharedSpaceController $controller */
        $controller = $this->getController(SharedSpaceController::class, $requestContentForJson);
        $controller->setApplicationsService($this->applicationsService);

        return $controller;
    }

    private function setTokenWithSharedSpaceId(string $token, string $userId, ?string $sharedSpaceId): void
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
            ->andReturn([
                'userId'        => $userId,
                'sharedSpaceId' => $sharedSpaceId,
            ])
            ->once();
    }

    public function testCreateActionSuccess()
    {
        $userId = 'user1';

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('create')
            ->with('My Space', $userId)
            ->andReturn([
                'sharedSpaceId' => 'shared-space-1',
                'name'          => 'My Space',
                'lpasMoved'     => 2,
            ])
            ->once();

        $controller = $this->getSharedSpaceController(['name' => 'My Space']);

        $result = $controller->createAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testCreateActionMissingName()
    {
        $userId = 'user1';

        $this->setToken('tokenval123', $userId);

        $controller = $this->getSharedSpaceController([]);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
    }

    public function testCreateActionAlreadyInSharedSpace()
    {
        $userId = 'user1';

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('create')
            ->with('My Space', $userId)
            ->andThrow(new UserAlreadyInSharedSpaceException())
            ->once();

        $controller = $this->getSharedSpaceController(['name' => 'My Space']);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
    }

    public function testCreateActionUnexpectedError()
    {
        $userId = 'user1';

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('create')
            ->with('My Space', $userId)
            ->andThrow(new RuntimeException('boom'))
            ->once();

        $controller = $this->getSharedSpaceController(['name' => 'My Space']);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(500, $result->toArray()['status']);
    }

    public function testLpasActionSuccess()
    {
        $userId = 'user1';
        $sharedSpaceId = 'shared-space-1';
        $lpa = FixturesData::getHwLpa();

        $this->setTokenWithSharedSpaceId('tokenval123', $userId, $sharedSpaceId);

        $this->params->shouldReceive('fromQuery')
            ->andReturn(['page' => 1, 'perPage' => 50]);

        $paginator = new Paginator(new ArrayAdapter([$lpa]));

        $this->applicationsService->shouldReceive('fetchAllForSharedSpace')
            ->with($sharedSpaceId, [])
            ->andReturn($paginator)
            ->once();

        $controller = $this->getSharedSpaceController();

        $result = $controller->lpasAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);

        $this->assertEquals(1, $body['total']);
        $this->assertCount(1, $body['applications']);
    }

    public function testLpasActionDeniedWhenNotInSharedSpace()
    {
        $userId = 'user1';

        $this->setTokenWithSharedSpaceId('tokenval123', $userId, null);

        $this->applicationsService->shouldNotReceive('fetchAllForSharedSpace');

        $controller = $this->getSharedSpaceController();

        $result = $controller->lpasAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testLpasActionInvalidToken()
    {
        $this->setToken('tokenval123', 'user1', false);

        $this->applicationsService->shouldNotReceive('fetchAllForSharedSpace');

        $controller = $this->getSharedSpaceController();

        $result = $controller->lpasAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }
}
