<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\ClientFactory;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Session\SessionUtility;
use Http\Client\HttpClient;
use Psr\Container\ContainerInterface;
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

        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'identity'])
            ->once()
            ->andReturn($userIdentity);

        $this->container->expects('get')->withArgs([SessionUtility::class])->once()->andReturn($sessionUtility);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateNoIdentity(): void
    {
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'identity'])
            ->once()
            ->andReturn(null);

        $this->container->expects('get')->withArgs([SessionUtility::class])->once()->andReturn($sessionUtility);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInstantiateRequestHasNoTraceIdHeader(): void
    {
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'identity'])
            ->once()
            ->andReturn(null);

        $this->container->expects('get')->withArgs([SessionUtility::class])->once()->andReturn($sessionUtility);

        /* @var $result Client */
        $result = ($this->factory)($this->container, [], null);

        $this->assertInstanceOf(Client::class, $result);
    }
}
