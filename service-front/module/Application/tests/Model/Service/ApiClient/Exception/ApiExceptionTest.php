<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\ApiClient\Exception;

use Application\Model\Service\ApiClient\Exception\ApiException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Utils;

final class ApiExceptionTest extends MockeryTestCase
{
    private ResponseInterface|MockInterface $response;

    public function setUp(): void
    {
        $this->response = Mockery::mock(ResponseInterface::class);
    }

    public function testConstructor(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor(null));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response, 'Exception message');

        $this->assertEquals('Exception message', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testConstructorMessageInResponse(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor('{"detail":"Body exception message"}'));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Body exception message', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testConstructorNoMessage(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor(null));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('HTTP:500 - Unexpected API response', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testGetTitle(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor('{"title":"Test Title"}'));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Title', $result->getTitle());
    }

    public function testGetTitleNotPresent(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor(null));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertNull($result->getTitle());
    }

    public function testGetData(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor('{"data":"Test Data"}'));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Data', $result->getData());
    }

    public function testGetDataWithKey(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor('{"data":{"test":"Test Data"}}'));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Data', $result->getData('test'));
    }

    public function testGetDataNotPresent(): void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(Utils::streamFor(null));
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertNull($result->getData('test'));
    }
}
