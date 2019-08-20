<?php

namespace ApplicationTest\Library\Authentication;

use Application\Library\Authentication\AuthenticationListener;
use Application\Library\Authentication\Identity\Guest;
use Application\Model\Service\Authentication\Service;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Zend\Authentication\Result;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Http\Request;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ApiProblem\ApiProblemResponse;

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
     * @var Zend\Authentication\AuthenticationService|MockInterface
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

    public function setUp(): void
    {
        $this->authService = Mockery::mock(Zend\Authentication\AuthenticationService::class);

        $this->serviceManager = Mockery::mock(ServiceLocatorInterface::class);
        $this->serviceManager->shouldReceive('get')->with('Zend\Authentication\AuthenticationService')
            ->andReturn($this->authService)->once();

        $this->application = Mockery::mock(ApplicationInterface::class);
        $this->application->shouldReceive('getServiceManager')->andReturn($this->serviceManager)->once();

        $this->request = Mockery::mock(Request::class);

        $this->mvcEvent = Mockery::mock(MvcEvent::class);
        $this->mvcEvent->shouldReceive('getApplication')->andReturn($this->application)->once();
        $this->mvcEvent->shouldReceive('getRequest')->andReturn($this->request)->once();
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
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertEquals(null, $result);
    }

    public function testAuthenticationFailed(): void
    {
        $header = Mockery::mock();
        $header->shouldReceive('getFieldValue')->andReturn('value')->once();

        $this->request->shouldReceive('getHeader')->with('Token')->andReturn($header)->once();

        $authenticationResult = Mockery::mock(Result::class);
        $authenticationResult->shouldReceive('getCode')->andReturn(Result::FAILURE)->once();

        $this->authService->shouldReceive('authenticate')->andReturn($authenticationResult)->once();

        $this->serviceManager->shouldReceive('get')->with('Config')
            ->andReturn(['admin' => ['accounts' => ['value']]])->once();
        $this->serviceManager->shouldReceive('get')->with(Service::class)
            ->andReturn(Mockery::mock(Service::class))->once();

        $authenticationListener = new AuthenticationListener();
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $this->assertEquals(401, $result->getStatusCode());

        $apiProblem = $result->getApiProblem();
        $this->assertEquals(
            ['type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Unauthorized',
                'status' => 401,
                'detail' => 'Invalid authentication token'], $apiProblem->toArray());
    }

    public function testAuthenticationNoToken(): void
    {
        $this->request->shouldReceive('getHeader')->with('Token')->andReturn(null)->once();

        $storage = Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('write')->with(Mockery::type(Guest::class))->once();

        $this->authService->shouldReceive('getStorage')->andReturn($storage)->once();

        $authenticationListener = new AuthenticationListener();
        $result = $authenticationListener->authenticate($this->mvcEvent);

        $this->assertEquals(null, $result);
    }
}
