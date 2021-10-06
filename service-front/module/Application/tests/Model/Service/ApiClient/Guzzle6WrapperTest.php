<?php

namespace ApplicationTest\Model\Service\ApiClient;

use ReflectionProperty;
use Http\Adapter\Guzzle6\Client as Guzzle6Adapter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\Service\ApiClient\Guzzle6Wrapper;

class Guzzle6WrapperTest extends MockeryTestCase
{
    public function testSend()
    {
        $adapterMock = Mockery::mock(Guzzle6Adapter::class);

        $requestMock = Mockery::mock(RequestInterface::class);
        $responseMock = Mockery::mock(ResponseInterface::class);

        $adapterMock->shouldReceive('sendRequest')
            ->with($requestMock)
            ->andReturn($responseMock);

        $wrapper = new Guzzle6Wrapper($adapterMock);
        $response = $wrapper->sendRequest($requestMock);

        $this->assertEquals($responseMock, $response);
    }
}
