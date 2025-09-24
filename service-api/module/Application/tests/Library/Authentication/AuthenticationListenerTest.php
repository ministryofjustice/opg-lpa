<?php

namespace ApplicationTest\Library\Authentication;

use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Authentication\AuthenticationListener;
use Application\Library\Authentication\Identity\Guest;
use Application\Model\Service\Authentication\Service;
use ApplicationTest\Library\Authentication\Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Authentication\Storage\StorageInterface;
use Laminas\Http\Request;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

class AuthenticationListenerTest extends MockeryTestCase
{
    /**
     * @var ApplicationInterface|MockInterface
     */
    private $application;

    /**
     * @var ServiceLocatorInterface|MockInterface
     */
    private $serviceManager;

    /**
     * @var Laminas\Authentication\AuthenticationService|MockInterface
     */
    private $authService;

    /**
     * @var Request|MockInterface
     */
    private $request;

    /**
     * @var MvcEvent|MockInterface
     */
    private $mvcEvent;
    private LoggerInterface|MockInterface $logger;

    public function setUp(): void
    {
        $this->authService = Mockery::mock(AuthenticationService::class);

        $this->serviceManager = Mockery::mock(ServiceLocatorInterface::class);
        $this->serviceManager->shouldReceive('get')->with('Laminas\Authentication\AuthenticationService')
            ->andReturn($this->authService)->once();

        $this->application = Mockery::mock(ApplicationInterface::class);
        $this->application->shouldReceive('getServiceManager')->andReturn($this->serviceManager)->once();

        $this->request = Mockery::mock(Request::class);

        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->mvcEvent->shouldReceive('getApplication')->andReturn($this->application)->once();
        $this->mvcEvent->shouldReceive('getRequest')->andReturn($this->request)->once();
        $this->logger = Mockery::spy(LoggerInterface::class);
    }

    public function testAuthenticationSuccess(): void
    {
        $header = Mockery::mock();
        $header->shouldReceive('getFieldValue')->andReturn('value')->once();

        $this->request->shouldReceive('getHeader')->with('Token')->andReturn($header)->once();

        $authenticationResult = Mockery::mock(Result::class);
        $authenticationResult->shouldReceive('getCode')->andReturn(Result::SUCCESS)->once();

        $this->authService->shouldReceive('authenticate')->andReturn($authenticationResult)->once();

        $this->serviceManager->shouldReceive('get')->with('Config')
            ->andReturn(['admin' => ['accounts' => ['value']]])->once();
        $this->serviceManager->shouldReceive('get')->with(Service::class)
            ->andReturn(Mockery::mock(Service::class))->once();

        $authenticationListener = new AuthenticationListener();
        $authenticationListener->setLogger($this->logger);
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertEquals(null, $result);
    }

    #[DataProvider('authenticationFailureDataProvider')]
    public function testAuthenticationFailed($authenticationResultCode, $expectedStatusCode, $title, $detail): void
    {
        $header = Mockery::mock();
        $header->shouldReceive('getFieldValue')->andReturn('value')->once();

        $this->request->shouldReceive('getHeader')->with('Token')->andReturn($header)->once();

        $authenticationResult = Mockery::mock(Result::class);
        $authenticationResult->shouldReceive('getCode')
            ->andReturn($authenticationResultCode)
            ->once();

        $this->authService->shouldReceive('authenticate')->andReturn($authenticationResult)->once();

        $this->serviceManager->shouldReceive('get')->with('Config')
            ->andReturn(['admin' => ['accounts' => ['value']]])->once();
        $this->serviceManager->shouldReceive('get')->with(Service::class)
            ->andReturn(Mockery::mock(Service::class))->once();

        $authenticationListener = new AuthenticationListener();
        $authenticationListener->setLogger($this->logger);
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals($expectedStatusCode, $result->getStatusCode());

        $apiProblem = $result->getApiProblem();
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => $title,
                'status' => $expectedStatusCode,
                'detail' => $detail
            ],
            $apiProblem->toArray()
        );
    }

    public static function authenticationFailureDataProvider(): array
    {
        return [
            'Invalid token' => [
                Result::FAILURE_CREDENTIAL_INVALID,
                401,
                'Unauthorized',
                'Invalid authentication token'
            ],
            'Database error' => [
                Result::FAILURE,
                500,
                'Internal Server Error',
                'Uncategorised error'
            ],
        ];
    }

    public function testAuthenticationNoToken(): void
    {
        $this->request->shouldReceive('getHeader')->with('Token')->andReturn(null)->once();

        $storage = Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('write')->with(Mockery::type(Guest::class))->once();

        $this->authService->shouldReceive('getStorage')->andReturn($storage)->once();

        $authenticationListener = new AuthenticationListener();
        $authenticationListener->setLogger($this->logger);
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertEquals(null, $result);
    }
}
