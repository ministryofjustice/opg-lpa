<?php

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\ClientFactory;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Http\Client\HttpClient;
use Interop\Container\ContainerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ClientFactoryTest extends MockeryTestCase
{
    /**
     * @var MockInterface|ContainerInterface
     */
    protected $container;

    /**
     * @var ClientFactory
     */
    protected $factory;

    public function setUp() : void
    {
        $httpClient = Mockery::mock(HttpClient::class);

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->expects('get')->withArgs(['HttpClient'])->once()->andReturn($httpClient);

        $this->container
            ->expects('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn(['api_client' => ['api_uri' => '/test/base/url']]);

        $this->factory = new ClientFactory();
    }

    private function makeMockRequest()
    {
        $mockTraceIdHeader = Mockery::mock(HeaderInterface::class);
        $mockTraceIdHeader->shouldReceive('getFieldValue')->once()->andReturn('traceid');

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('getHeader')->once()->andReturn($mockTraceIdHeader);

        return $mockRequest;
    }

    public function testInstantiate() : void
    {
        $userIdentity = Mockery::mock(UserIdentity::class);
        $userIdentity->shouldReceive('token')->once()->andReturn('Test Token');

        $request = $this->makeMockRequest();
        $this->container->expects('get')->withArgs(['Request'])->once()->andReturn($request);

        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = $userIdentity;

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateNoIdentity() : void
    {
        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = null;

        $request = $this->makeMockRequest();
        $this->container->expects('get')->withArgs(['Request'])->once()->andReturn($request);

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateNoRequest() : void
    {
        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = null;

        $this->container->expects('get')->withArgs(['Request'])->once()->andReturn(null);

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateRequestHasNoTraceIdHeader() : void
    {
        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = null;

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->expects('getHeader')->once()->andReturn(FALSE);

        $this->container->expects('get')->withArgs(['Request'])->once()->andReturn($mockRequest);

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }
}
