<?php

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Hamcrest\Matchers;
use Http\Client\HttpClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends MockeryTestCase
{
    /**
     * @var HttpClient|MockInterface
     */
    private $httpClient;

    /**
     * @var ResponseInterface|MockInterface
     */
    private $response;

    /**
     * @var Client
     */
    private $client;

    public function setUp() : void
    {
        $this->httpClient = Mockery::mock(HttpClient::class);

        $this->client = new Client($this->httpClient, 'base_url/', 'test token');
    }

    public function setUpRequest(
        $returnStatus = 200,
        $returnBody = '{"test": "value"}',
        $verb = 'GET',
        $url = 'base_url/path',
        $requestData = null,
        $token = 'test token'
    ) {
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->response->shouldReceive('getStatusCode')->once()->andReturn($returnStatus);

        if ($returnBody != null) {
            $this->response->shouldReceive('getBody')->once()->andReturn($returnBody);
        }

        if ($requestData == null) {
            $this->httpClient->shouldReceive('sendRequest')
                ->withArgs(
                    [Matchers::equalTo(
                        new Request(
                            $verb,
                            new Uri($url),
                            ['Accept' => 'application/json',
                                'Content-type' => 'application/json',
                                'User-agent' => 'LPA-FRONT',
                                'Token' => $token]
                        )
                    )]
                )
                ->once()
                ->andReturn($this->response);
        } else {
            // withArgs is having to be omitted where a body is sent due to the streamsin the Request being a different
            // size
            $this->httpClient->shouldReceive('sendRequest')
                ->once()
                ->andReturn($this->response);
        }
    }

    /**
     *  As the token is private, test that it is updated indirectly by seeing what is added to a request header
     * @throws \Http\Client\Exception
     */
    public function testUpdateToken() : void
    {
        $this->client->updateToken('new token');

        $this->setUpRequest(
            200,
            '{"test": "value"}',
            'GET',
            'base_url/path',
            null,
            'new token'
        );

        $this->client->httpGet('path');
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function testHttpGet() : void
    {
        $this->setUpRequest();

        $result = $this->client->httpGet('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function testHttpGetWithQuery() : void
    {
        $this->setUpRequest(200, '{"test":"value"}', 'GET', 'base_url/path?a=1');

        $result = $this->client->httpGet('path', ['a' => '1']);

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function testHttpGetJsonFalse() : void
    {
        $this->setUpRequest();

        $result = $this->client->httpGet('path', [], false);

        $this->assertEquals('{"test": "value"}', $result);
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:204 - Unexpected API response
     */
    public function testHttpGetNoContent() : void
    {
        $this->setUpRequest(204, null);
        $this->response->shouldReceive('getBody')->once();
        $this->response->shouldReceive('getStatusCode')->twice()->andReturn(204);

        $this->client->httpGet('path');
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:404 - Unexpected API response
     */
    public function testHttpGetNotFound() : void
    {
        $this->setUpRequest(404, null);
        $this->response->shouldReceive('getBody')->once();
        $this->response->shouldReceive('getStatusCode')->twice()->andReturn(404);
        $this->client->httpGet('path');

    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:500 - Unexpected API response
     * @throws \Http\Client\Exception
     */
    public function testHttpError() : void
    {
        $this->setUpRequest(500, 'An error');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $this->client->httpGet('path');
    }

    public function testHttpDelete() : void
    {
        $this->setUpRequest(204, null, 'DELETE');

        $result = $this->client->httpDelete('path');

        $this->assertNull($result);
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:500 - Unexpected API response
     */
    public function testHttpDeleteError() : void
    {
        $this->setUpRequest(500, 'An error', 'DELETE');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $this->client->httpDelete('path');
    }

    public function testHttpPatch() : void
    {
        $this->setUpRequest(
            200,
            '{"test": "value"}',
            'PATCH',
            'base_url/path',
            '{"a":1}'
        );

        $result = $this->client->httpPatch('path', ['a' => 1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPatchAccept() : void
    {
        $this->setUpRequest(
            201,
            '{"test": "value"}',
            'PATCH',
            'base_url/path',
            '{"a":1}'
        );

        $result = $this->client->httpPatch('path', ['a'=>1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:500 - Unexpected API response
     */
    public function testHttpPatchError() : void
    {
        $this->setUpRequest(500, 'An error', 'PATCH', 'base_url/path', '{"a":1}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $this->client->httpPatch('path');
    }

    public function testHttpPost() : void
    {
        $this->setUpRequest(200, '{"test": "value"}', 'POST', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPost('path', ['a'=>1]);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPostAccept() : void
    {
        $this->setUpRequest(201, '{"test": "value"}', 'POST', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPost('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:500 - Unexpected API response
     */
    public function testHttpPostError() : void
    {
        $this->setUpRequest(500, 'An error', 'POST', 'base_url/path', '{"a":1}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $this->client->httpPost('path');
    }

    public function testHttpPut() : void
    {
        $this->setUpRequest(200, '{"test": "value"}', 'PUT', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPut('path', []);

        $this->assertEquals(['test' => 'value'], $result);
    }

    public function testHttpPutAccept() : void
    {
        $this->setUpRequest(201, '{"test": "value"}', 'PUT', 'base_url/path', '{"a":1}');

        $result = $this->client->httpPut('path');

        $this->assertEquals(['test' => 'value'], $result);
    }

    /**
     * @expectedException  Application\Model\Service\ApiClient\Exception\ApiException
     * @expectedExceptionMessage HTTP:500 - Unexpected API response
     */
    public function testHttpPutError() : void
    {
        $this->setUpRequest(500, 'An error', 'PUT', 'base_url/path', '{"a":1}');
        $this->response->shouldReceive('getStatusCode')->times(2)->andReturn(500);

        $this->client->httpPut('path');
    }
}
