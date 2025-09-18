<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\ClientFactory;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Http\Client\HttpClient;
use Interop\Container\ContainerInterface;
use MakeShared\Telemetry\Tracer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class ClientFactoryTest extends MockeryTestCase
{
    private MockInterface|ContainerInterface $container;
    private ClientFactory $factory;

    public function setUp(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $tracer = Mockery::mock(Tracer::class);

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->expects('get')->withArgs(['HttpClient'])->once()->andReturn($httpClient);
        $this->container->expects('get')->withArgs(['TelemetryTracer'])->once()->andReturn($tracer);

        $this->container
            ->expects('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn(['api_client' => ['api_uri' => '/test/base/url']]);

        $this->factory = new ClientFactory();
    }

    public function testInstantiate(): void
    {
        $userIdentity = Mockery::mock(UserIdentity::class);
        $userIdentity->shouldReceive('token')->once()->andReturn('Test Token');

        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = $userIdentity;

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateNoIdentity(): void
    {
        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = null;

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateRequestHasNoTraceIdHeader(): void
    {
        $userDetailsSession = new MockUserDetailsSession();
        $userDetailsSession->identity = null;

        $this->container->expects('get')->withArgs(['UserDetailsSession'])->once()->andReturn($userDetailsSession);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }
}
