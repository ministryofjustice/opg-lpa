<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\UsersController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\Users\Service;
use Laminas\Http\Request as HttpRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use RuntimeException;

class UsersControllerTest extends MockeryTestCase
{
    private MockInterface|SharedSpaceService $sharedSpaceService;
    private MockInterface|Service $service;
    private MockInterface|AuthenticationService $authenticationService;
    private MockInterface|LoggerInterface $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->sharedSpaceService = Mockery::mock(SharedSpaceService::class);
        $this->service = Mockery::mock(Service::class);
        $this->authenticationService = Mockery::mock(AuthenticationService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    private function getController(): UsersController
    {
        $controller = new UsersController(
            $this->sharedSpaceService,
            $this->service,
            $this->authenticationService,
            $this->logger
        );

        $controller->getResponse();

        return $controller;
    }

    /**
     * Injects a request with (or without) a Token header directly into the controller's
     * protected $request property, since delete() reads it via getRequest() and we're
     * calling delete() directly rather than going through the full dispatch()/routing cycle.
     */
    private function setRequestToken(UsersController $controller, ?string $token): void
    {
        $request = new HttpRequest();

        if ($token !== null) {
            $request->getHeaders()->addHeaderLine('Token', $token);
        }

        $property = new ReflectionProperty($controller, 'request');
        $property->setAccessible(true);
        $property->setValue($controller, $request);
    }

    public function testCreateActivateAccount()
    {
        $activationToken = 'ackToken';

        $this->service->shouldReceive('activate')
            ->with($activationToken)
            ->andReturnTrue()
            ->once();

        $this->logger->shouldReceive('info')
            ->with('New user account activated', [
                'activation_token' => $activationToken,
            ]);

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->create([
            'activationToken' => $activationToken,
        ]);

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testCreateActivateAccountFailedCantActivate()
    {
        $activationToken = 'ackToken';

        $this->service->shouldReceive('activate')
            ->with($activationToken)
            ->andReturn('Failure reason')
            ->once();

        $controller = $this->getController();

        /** @var ApiProblem $result */
        $result = $controller->create([
            'activationToken' => $activationToken,
        ]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Failure reason', $data['detail']);
    }

    public function testCreateNewAccount()
    {
        $username = 'user@name.com';
        $password = 'P@55word';

        $accountCreateReturnData = [
            'userId'           => 'qqwertyuiuyt23456789876',
            'activation_token' => 'ackToken',
        ];

        $this->service->shouldReceive('create')
            ->with($username, $password)
            ->andReturn($accountCreateReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('New user account created', $accountCreateReturnData);

        $controller = $this->getController();

        /** @var Json $result */
        $result = $controller->create([
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertInstanceOf(Json::class, $result);
    }

    public function testCreateNewAccountFailed()
    {
        $username = 'user@name.com';
        $password = 'P@55word';

        $this->service->shouldReceive('create')
            ->with($username, $password)
            ->andReturn('Failure reason')
            ->once();

        $controller = $this->getController();

        /** @var ApiProblem $result */
        $result = $controller->create([
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Failure reason', $data['detail']);
    }

    public function testCreateFailedNoData()
    {
        $controller = $this->getController();

        /** @var ApiProblem $result */
        $result = $controller->create([]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Either activationToken or username & password must be passed', $data['detail']);
    }

    public function testDeleteReturnsUnauthorizedWhenNoTokenHeader()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, null);

        $this->authenticationService->shouldNotReceive('withToken');
        $this->sharedSpaceService->shouldNotReceive('deleteAccount');
        $this->service->shouldNotReceive('delete');

        /** @var ApiProblem $result */
        $result = $controller->delete('user-1');

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();
        $this->assertEquals(401, $data['status']);
        $this->assertEquals('invalid-token', $data['detail']);
    }

    public function testDeleteReturnsUnauthorizedWhenTokenInvalid()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn('invalid-token');

        $this->sharedSpaceService->shouldNotReceive('deleteAccount');
        $this->service->shouldNotReceive('delete');

        /** @var ApiProblem $result */
        $result = $controller->delete('user-1');

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();
        $this->assertEquals(401, $data['status']);
        $this->assertEquals('invalid-token', $data['detail']);
    }

    public function testDeleteReturnsUnauthorizedWhenTokenMissingUserId()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['expiresAt' => null]);

        $this->sharedSpaceService->shouldNotReceive('deleteAccount');
        $this->service->shouldNotReceive('delete');

        /** @var ApiProblem $result */
        $result = $controller->delete('user-1');

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();
        $this->assertEquals(401, $data['status']);
        $this->assertEquals('invalid-token', $data['detail']);
    }

    public function testDeleteDeletesUserWhenNotInSharedSpace()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['userId' => 'user-1', 'sharedSpaceId' => null]);

        $this->service->shouldReceive('delete')
            ->with('user-1')
            ->once()
            ->andReturn(true);

        $this->sharedSpaceService->shouldNotReceive('deleteAccount');

        $result = $controller->delete('user-1');

        $this->assertInstanceOf(NoContent::class, $result);
    }

    public function testDeleteReturnsApiProblemWhenUserServiceReturnsApiProblem()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['userId' => 'user-1', 'sharedSpaceId' => null]);

        $apiProblem = new ApiProblem(500, 'Something went wrong');

        $this->service->shouldReceive('delete')
            ->with('user-1')
            ->once()
            ->andReturn($apiProblem);

        $result = $controller->delete('user-1');

        $this->assertSame($apiProblem, $result);
    }

    public function testDeleteReturnsErrorWhenUserServiceThrows()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['userId' => 'user-1', 'sharedSpaceId' => null]);

        $exception = new RuntimeException('Database error');

        $this->service->shouldReceive('delete')
            ->with('user-1')
            ->once()
            ->andThrow($exception);

        $this->logger->shouldReceive('error')
            ->with('Error deleting user', ['exception' => $exception])
            ->once();

        /** @var ApiProblem $result */
        $result = $controller->delete('user-1');

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();
        $this->assertEquals(500, $data['status']);
        $this->assertEquals('Unable to process request', $data['detail']);
    }

    public function testDeleteDeletesSharedSpaceAccountWhenInSharedSpace()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['userId' => 'user-1', 'sharedSpaceId' => 'shared-space-1']);

        $this->sharedSpaceService->shouldReceive('deleteAccount')
            ->with('shared-space-1', 'user-1')
            ->once();

        $this->service->shouldNotReceive('delete');

        $result = $controller->delete('user-1');

        $this->assertInstanceOf(NoContent::class, $result);
    }

    public function testDeleteReturnsErrorWhenSharedSpaceServiceThrows()
    {
        $controller = $this->getController();
        $this->setRequestToken($controller, 'a-token');

        $this->authenticationService->shouldReceive('withToken')
            ->with('a-token', false)
            ->once()
            ->andReturn(['userId' => 'user-1', 'sharedSpaceId' => 'shared-space-1']);

        $exception = new RuntimeException('Transaction failed');

        $this->sharedSpaceService->shouldReceive('deleteAccount')
            ->with('shared-space-1', 'user-1')
            ->once()
            ->andThrow($exception);

        $this->service->shouldNotReceive('delete');

        $this->logger->shouldReceive('error')
            ->with('Error deleting user', ['exception' => $exception])
            ->once();

        /** @var ApiProblem $result */
        $result = $controller->delete('user-1');

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();
        $this->assertEquals(500, $data['status']);
        $this->assertEquals('Unable to process request', $data['detail']);
    }
}
