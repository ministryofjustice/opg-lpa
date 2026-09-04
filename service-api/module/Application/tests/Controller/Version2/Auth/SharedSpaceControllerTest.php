<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\SharedSpaceController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Entity\MemberInvite;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\SharedSpace\InviteAlreadyExistsException;
use Application\Model\Service\SharedSpace\InviteNotFoundException;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
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
use PHPUnit\Framework\Attributes\DataProvider;
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
        $sharedSpaceName = 'My space';
        $lpa = FixturesData::getHwLpa();

        $this->withParams()->shouldReceive('fromQuery')
            ->andReturn(['page' => 1, 'perPage' => 50]);

        $paginator = new Paginator(new ArrayAdapter([$lpa]));

        $this->applicationsService->shouldReceive('fetchAllForSharedSpace')
            ->with($sharedSpaceId, [])
            ->andReturn($paginator)
            ->once();

        $this->sharedSpaceService->shouldReceive('getName')
            ->andReturn($sharedSpaceName);

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->lpasAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);

        $this->assertEquals(1, $body['total']);
        $this->assertCount(1, $body['applications']);
        $this->assertEquals($sharedSpaceName, $body['name']);
    }

    public function testLpasActionWhenSpaceHasNoName()
    {
        $userId = 'user1';
        $sharedSpaceId = 'shared-space-1';

        $this->withParams()->shouldReceive('fromQuery')
            ->andReturn(['page' => 1, 'perPage' => 50]);

        $this->sharedSpaceService->shouldReceive('getName')
            ->andReturn(null);

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->lpasAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(404, $result->toArray()['status']);
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

    public function testMemberAction()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $memberUserId = 'member-user';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn($memberUserId);

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true)
            ->once();

        $this->sharedSpaceService->shouldReceive('getMember')
            ->with($sharedSpaceId, $memberUserId)
            ->andReturn(['id' => $memberUserId, 'isAdmin' => false])
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->memberAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['member' => ['id' => $memberUserId, 'isAdmin' => false]], $body);
    }

    public function testMemberActionNotFound()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $memberUserId = 'member-user';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn($memberUserId);

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true)
            ->once();

        $this->sharedSpaceService->shouldReceive('getMember')
            ->with($sharedSpaceId, $memberUserId)
            ->andReturn(null)
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->memberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(404, $result->toArray()['status']);
    }

    public function testMemberActionDeniedWhenNotAdmin()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(false)
            ->once();

        $this->sharedSpaceService->shouldNotReceive('getMember');

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->memberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testMemberActionWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->sharedSpaceService->shouldNotReceive('isAdmin');
        $this->sharedSpaceService->shouldNotReceive('getMember');

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->memberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testMemberActionWhenInvalidToken()
    {
        $this->sharedSpaceService->shouldNotReceive('isAdmin');
        $this->sharedSpaceService->shouldNotReceive('getMember');

        $this->makeRequest(false);
        $result = $this->controller->memberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }

    public function testMembersAndInvitesAction()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('getMembers')
            ->with($sharedSpaceId)
            ->andReturn(['a' => 'b']);

        $this->sharedSpaceService->shouldReceive('getInvites')
            ->with($sharedSpaceId)
            ->andReturn(['c' => 'd']);

        $this->sharedSpaceService->shouldReceive('getName')
            ->with($sharedSpaceId)
            ->andReturn('Example Shared Space');

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId]);
        $result = $this->controller->membersAndInvitesAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['members' => ['a' => 'b'], 'invites' => ['c' => 'd'], 'name' => 'Example Shared Space'], $body);
    }

    public function testMembersAndInvitesActionWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->membersAndInvitesAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testMembersAndInvitesActionWhenInvalidToken()
    {
        $this->makeRequest(false);
        $result = $this->controller->membersAndInvitesAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }

    public function testInviteAction()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('invite')
            ->with(Mockery::on(function (MemberInvite $invite) use ($userId, $sharedSpaceId): bool {
                $createdDiff = $invite->created->getTimestamp() - new DateTime()->getTimestamp();
                $expiresDiff = $invite->expires->getTimestamp() - new DateTime('+7 days')->getTimestamp();

                return $invite->userId === $userId
                    && $invite->sharedSpaceId === $sharedSpaceId
                    && $invite->firstNames === '1'
                    && $invite->lastName === '2'
                    && $invite->email === '3'
                    && $invite->isAdmin
                    && strlen($invite->code) === 8
                    && $createdDiff === 0
                    && $expiresDiff === 0;
            }))
            ->andReturn(['a' => 'b']);

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ], [
            'firstNames' => '1',
            'lastName' => '2',
            'email' => '3',
            'isAdmin' => true,
        ]);
        $result = $this->controller->inviteAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['a' => 'b'], $body);
    }

    public static function existingResourceProvider(): array
    {
        return [
            [new UserAlreadyInSharedSpaceException(), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, 'user-already-in-shared-space'],
            [new InviteAlreadyExistsException(), StatusCodeInterface::STATUS_CONFLICT, 'invite-already-exists'],
            [new RuntimeException('something wrong'), StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, 'Unable to process request: something wrong'],
        ];
    }

    #[DataProvider('existingResourceProvider')]
    public function testInviteActionWhenUserAlreadyInSharedSpace(RuntimeException $exception, int $expectedStatus, string $expectedDetail)
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('invite')
            ->with(Mockery::any())
            ->andThrow($exception);

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ], [
            'firstNames' => '1',
            'lastName' => '2',
            'email' => '3',
            'isAdmin' => true,
        ]);
        $result = $this->controller->inviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $exception = $result->toArray();
        $this->assertEquals($expectedStatus, $exception['status']);
        $this->assertEquals($expectedDetail, $exception['detail']);
    }

    public function testInviteActionWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->inviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_FORBIDDEN, $result->toArray()['status']);
    }

    public function testInviteActionWhenInvalidToken()
    {
        $this->makeRequest(false);
        $result = $this->controller->inviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_UNAUTHORIZED, $result->toArray()['status']);
    }

    public function testInviteActionWhenNotAdmin()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(false);

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ], [
            'firstNames' => '1',
            'lastName' => '2',
            'email' => '3',
            'isAdmin' => true,
        ]);
        $result = $this->controller->inviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_FORBIDDEN, $result->toArray()['status']);
    }

    public function testUpdateMemberActionSuccess()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $memberUserId = 'member-user';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn($memberUserId);

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true)
            ->once();

        $this->sharedSpaceService->shouldReceive('updateMember')
            ->with($sharedSpaceId, $memberUserId, true, true)
            ->andReturn(true)
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId], ['isAdmin' => true, 'isActive' => true]);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['success' => true], $body);
    }

    public function testUpdateMemberWhenMemberNotInSharedSpace()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $memberUserId = 'member-user';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn($memberUserId);

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true)
            ->once();

        $this->sharedSpaceService->shouldReceive('updateMember')
            ->with($sharedSpaceId, $memberUserId, false, false)
            ->andThrow(new MemberNotInSharedSpaceException())
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId], ['isAdmin' => false, 'isActive' => false]);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(404, $result->toArray()['status']);
    }

    public function testUpdateMemberActionUnexpectedError()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $memberUserId = 'member-user';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn($memberUserId);

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true)
            ->once();

        $this->sharedSpaceService->shouldReceive('updateMember')
            ->with($sharedSpaceId, $memberUserId, true, false)
            ->andThrow(new RuntimeException('boom'))
            ->once();

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId], ['isAdmin' => true, 'isActive' => false]);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(500, $result->toArray()['status']);
    }

    public function testUpdateMemberActionDeniedWhenNotAdmin()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn('member-user');

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(false)
            ->once();

        $this->sharedSpaceService->shouldNotReceive('updateMember');

        $this->makeRequest(['userId' => $userId, 'sharedSpaceId' => $sharedSpaceId], ['isAdmin' => true]);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testUpdateMemberActionDeniedWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->sharedSpaceService->shouldNotReceive('isAdmin');
        $this->sharedSpaceService->shouldNotReceive('updateMember');

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testUpdateMemberActionInvalidToken()
    {
        $this->sharedSpaceService->shouldNotReceive('isAdmin');
        $this->sharedSpaceService->shouldNotReceive('updateMember');

        $this->makeRequest(false);
        $result = $this->controller->updateMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(401, $result->toArray()['status']);
    }

    public function testRevokeMemberInviteAction()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $inviteId = '5';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true);

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberInviteId')
            ->andReturn($inviteId);

        $this->sharedSpaceService->shouldReceive('revokeInvite')
            ->with($sharedSpaceId, $inviteId, $userId)
            ->andReturn(['a' => 'b']);

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ]);
        $result = $this->controller->revokeInviteAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['success' => true], $body);
    }

    public function testRevokeMemberInviteActionWhenNotInSharedSpace()
    {
        $userId = 'my-user';

        $this->makeRequest(['userId' => $userId]);
        $result = $this->controller->revokeInviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_FORBIDDEN, $result->toArray()['status']);
    }

    public function testRevokeMemberInviteActionWhenInvalidToken()
    {
        $this->makeRequest(false);
        $result = $this->controller->revokeInviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_UNAUTHORIZED, $result->toArray()['status']);
    }

    public function testRevokeMemberInviteActionWhenNotAdmin()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(false);

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ]);
        $result = $this->controller->revokeInviteAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(StatusCodeInterface::STATUS_FORBIDDEN, $result->toArray()['status']);
    }

    public function testRevokeMemberInviteActionWhenServiceException()
    {
        $userId = 'my-user';
        $sharedSpaceId = 'my-space';
        $inviteId = '5';

        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->andReturn(true);

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberInviteId')
            ->andReturn($inviteId);

        $this->sharedSpaceService->shouldReceive('revokeInvite')
            ->with($sharedSpaceId, $inviteId, $userId)
            ->andThrow(new \Exception('Something went wrong'));

        $this->makeRequest([
            'userId' => $userId,
            'sharedSpaceId' => $sharedSpaceId,
        ]);

        $result = $this->controller->revokeInviteAction();
        $this->assertInstanceOf(ApiProblem::class, $result);
    }

    public function testJoinAction()
    {
        $userId = 'my-user';
        $sharedSpaceName = 'My space';
        $accessCode = '1234';

        $this->sharedSpaceService->shouldReceive('join')
            ->with($userId, $sharedSpaceName, $accessCode);

        $this->makeRequest(['userId' => $userId], [
            'sharedSpaceName' => $sharedSpaceName,
            'accessCode' => $accessCode,
        ]);
        $result = $this->controller->joinAction();

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testJoinActionWhenAlreadyInSharedSpace()
    {
        $this->sharedSpaceService->shouldReceive('join')
            ->andThrow(new UserAlreadyInSharedSpaceException('my space'));

        $this->makeRequest(['userId' => '1'], ['sharedSpaceName' => '2', 'accessCode' => '3']);
        $result = $this->controller->joinAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
        $this->assertEquals('user-already-in-shared-space', $result->toArray()['detail']);
        $this->assertEquals('my space', $result->toArray()['sharedSpaceId']);
    }

    public function testJoinActionWhenInviteNotFound()
    {
        $this->sharedSpaceService->shouldReceive('join')
            ->andThrow(new InviteNotFoundException());

        $this->makeRequest(['userId' => '1'], ['sharedSpaceName' => '2', 'accessCode' => '3']);
        $result = $this->controller->joinAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(400, $result->toArray()['status']);
        $this->assertEquals('invite-not-found', $result->toArray()['detail']);
    }

    public function testDeleteMemberAction()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with('2', '1')
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('deleteMember')
            ->with('2', '1', 'member-id');

        $this->withParams()->shouldReceive('fromRoute')
            ->with('memberUserId')
            ->andReturn('member-id');

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], []);
        $result = $this->controller->deleteMemberAction();

        $this->assertInstanceOf(NoContent::class, $result);
    }

    public function testDeleteMemberActionWhenIsNotAdmin()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->andReturn(false);

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], []);
        $result = $this->controller->deleteMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }

    public function testDeleteMemberActionWhenNotInSharedSpace()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('deleteMember')
            ->andThrow(new MemberNotInSharedSpaceException());

        $this->withParams()->shouldReceive('fromRoute')
            ->andReturn('member-id');

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], []);
        $result = $this->controller->deleteMemberAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(404, $result->toArray()['status']);
    }

    public function testImportAction()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->with('2', '1')
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('import')
            ->with('2', '1', 'an email', 'pass');

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], [
            'email' => 'an email',
            'password' => 'pass', # pragma: allowlist secret
        ]);
        $result = $this->controller->importAction();

        $this->assertInstanceOf(NoContent::class, $result);
    }

    public function testImportActionWhenAuthProblem()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('import')
            ->andReturn('this-problem');

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], [
            'email' => 'an email',
            'password' => 'pass', # pragma: allowlist secret
        ]);
        $result = $this->controller->importAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['problem' => 'this-problem'], $body);
    }

    public function testImportActionWhenUserAlreadyInSpace()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->andReturn(true);

        $this->sharedSpaceService->shouldReceive('import')
            ->andThrow(new UserAlreadyInSharedSpaceException());

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], [
            'email' => 'an email',
            'password' => 'pass', # pragma: allowlist secret
        ]);
        $result = $this->controller->importAction();

        $this->assertInstanceOf(Json::class, $result);

        $body = json_decode($result->getContent(), true);
        $this->assertEquals(['problem' => 'user-already-in-space'], $body);
    }

    public function testImportActionWhenIsNotAdmin()
    {
        $this->sharedSpaceService->shouldReceive('isAdmin')
            ->andReturn(false);

        $this->makeRequest(['userId' => '1', 'sharedSpaceId' => '2'], [
            'email' => 'an email',
            'password' => 'pass', # pragma: allowlist secret
        ]);
        $result = $this->controller->importAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals(403, $result->toArray()['status']);
    }
}
