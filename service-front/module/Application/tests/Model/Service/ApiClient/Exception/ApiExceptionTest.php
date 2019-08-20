<?php

namespace ApplicationTest\Model\Service\ApiClient\Exception;

use Application\Model\Service\ApiClient\Exception\ApiException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

class ApiExceptionTest extends MockeryTestCase
{
    /**
     * @var ResponseInterface|MockInterface
     */
    private $response;

    public function setUp() : void
    {
        $this->response = Mockery::mock(ResponseInterface::class);
    }

    public function testConstructor() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(null);
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response, 'Exception message');

        $this->assertEquals('Exception message', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testConstructorMessageInResponse() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn('{"detail":"Body exception message"}');
        $this->response->shouldReceive('getStatusCode')->once()->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Body exception message', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testConstructorNoMessage() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(null);
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('HTTP:500 - Unexpected API response', $result->getMessage());
        $this->assertEquals(500, $result->getCode());
    }

    public function testGetTitle() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn('{"title":"Test Title"}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Title', $result->getTitle());
    }

    public function testGetTitleNotPresent() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(null);
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertNull($result->getTitle());
    }

    public function testGetData() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn('{"data":"Test Data"}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Data', $result->getData());
    }

    public function testGetDataWithKey() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn('{"data":{"test":"Test Data"}}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertEquals('Test Data', $result->getData('test'));
    }

    public function testGetDataNotPresent() : void
    {
        $this->response->shouldReceive('getBody')->once()->andReturn(null);
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $result = new ApiException($this->response);

        $this->assertNull($result->getData('test'));
    }
}
