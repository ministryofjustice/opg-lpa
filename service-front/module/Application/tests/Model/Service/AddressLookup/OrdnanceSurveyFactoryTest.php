<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\AddressLookup;

use UnexpectedValueException;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Interop\Container\ContainerInterface;
use Http\Client\HttpClient as HttpClientInterface;

use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\AddressLookup\OrdnanceSurveyFactory;

final class OrdnanceSurveyFactoryTest extends MockeryTestCase
{
    private MockInterface|ContainerInterface $container;

    protected function setUp() : void
    {
        $this->container = Mockery::mock(ContainerInterface::class);
    }

    public function testCanInstantiate(): void
    {
        $factory = new OrdnanceSurveyFactory();
        $this->assertInstanceOf(OrdnanceSurveyFactory::class, $factory);
    }

    public function testInvalidApiKeyConfiguration(): void
    {
        $this->container->shouldReceive('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn(['address'=>['ordnancesurvey'=>['endpoint'=>'xxx']]]);

        $factory = new OrdnanceSurveyFactory();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/Ordnance Survey API key not configured/');

        $factory($this->container, null);
    }

    public function testInvalidEndpointConfiguration(): void
    {
        $this->container->shouldReceive('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn(['address'=>['ordnancesurvey'=>['key'=>'xxx']]]);

        $factory = new OrdnanceSurveyFactory();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/Ordnance Survey API endpoint not configured/');

        $factory($this->container, null);
    }


    public function testWithValidConfiguration(): void
    {
        $this->container->shouldReceive('get')
            ->withArgs(['config'])
            ->once()
            ->andReturn(['address'=>['ordnancesurvey'=>['key'=>'xxx', 'endpoint'=> 'http://yyyy.example.com']]]);

        $this->container->shouldReceive('get')
            ->withArgs(['HttpClient'])
            ->once()
            ->andReturn(Mockery::mock(HttpClientInterface::class));

        $factory = new OrdnanceSurveyFactory();

        $result = $factory($this->container, null);

        $this->assertInstanceOf(OrdnanceSurvey::class, $result);
    }
}
