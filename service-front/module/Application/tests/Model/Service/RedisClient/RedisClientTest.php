<?php
namespace ApplicationTest\Model\Service\RedisClient;

use Application\Model\Service\RedisClient\RedisClient;
use InvalidArgumentException;
use Redis;
use ReflectionProperty;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;


class RedisClientTest extends MockeryTestCase
{
    private $client;

    // returns the mock for setting expectations
    private function makeClientWithMock(string $redisUrl): Redis {
        $redisMock = Mockery::Mock(Redis::class);
        $this->client = new RedisClient($redisUrl, $redisMock);
        return $redisMock;
    }

    public function tearDown(): void
    {
        $this->client = null;
    }

    // combinations of set and close error responses which result in
    // an error when calling set()
    public function errorConditionsForSet()
    {
        return [
            ['setResult' => TRUE, 'closeResult' => FALSE],
            ['setResult' => FALSE, 'closeResult' => TRUE],
            ['setResult' => FALSE, 'closeResult' => FALSE],
        ];
    }

    // combinations of set and close error responses which result in
    // an error when calling get()
    public function errorConditionsForGet()
    {
        return [
            ['getResult' => TRUE, 'closeResult' => FALSE],
            ['getResult' => FALSE, 'closeResult' => TRUE],
            ['getResult' => FALSE, 'closeResult' => FALSE],
        ];
    }

    public function testConstructor_InstantiatesRedis(): void
    {
        // use reflection to check that the CsrfClient has a Redis
        // instance instantiated where one is not explicitly passed to
        // the constructor
        $redisProperty = new ReflectionProperty(RedisClient::class, 'redis');
        $redisProperty->setAccessible(true);

        $client = new RedisClient('tcp://localhost');

        $this->assertInstanceOf(Redis::class, $redisProperty->getValue($client));
    }

    public function testConstructor_IncludesTls(): void
    {
        // use reflection to check that the host extracted for a TLS
        // hostname includes the tls scheme
        $redisHostProperty = new ReflectionProperty(RedisClient::class, 'redisHost');
        $redisHostProperty->setAccessible(true);

        $client = new RedisClient('tls://localhost');

        $this->assertEquals('tls://localhost', $redisHostProperty->getValue($client));
    }

    public function testConstructor_InvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new RedisClient('foohost');
    }

    public function testSetError_ConnectFail(): void
    {
        $expected = FALSE;

        $redisMock = $this->makeClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(FALSE);

        $actual = $this->client->set('foo', 'bar');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider errorConditionsForSet
     */
    public function testSetError_SetAndOrCloseFail($setResult, $closeResult): void
    {
        $expected = FALSE;

        $redisMock = $this->makeClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(TRUE);
        $redisMock->shouldReceive('set')
            ->with('foo', 'bar')
            ->andReturn($setResult);
        $redisMock->shouldReceive('close')
            ->andReturn($closeResult);

        $actual = $this->client->set('foo', 'bar');

        $this->assertEquals($expected, $actual);
    }

    public function testSetSuccess_UsesDefaultPort(): void
    {
        $redisMock = $this->makeClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(TRUE);
        $redisMock->shouldReceive('set')
            ->with('foo', 'bar')
            ->andReturn(TRUE);
        $redisMock->shouldReceive('close')
            ->andReturn(TRUE);

        $actual = $this->client->set('foo', 'bar');

        $this->assertEquals(TRUE, $actual);
    }

    public function testGetError_ConnectFail(): void
    {
        $expected = FALSE;

        $redisMock = $this->makeClientWithMock('tcp://foohost');
        $redisMock->shouldReceive('connect')
            ->with('foohost', 6379)
            ->andReturn(FALSE);

        $actual = $this->client->get('baz');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider errorConditionsForGet
     */
    public function testGetError_GetAndOrCloseFail($getResult, $closeResult): void
    {
        $expected = FALSE;

        $redisMock = $this->makeClientWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('connect')
            ->with('barhost', 6737)
            ->andReturn(TRUE);
        $redisMock->shouldReceive('get')
            ->with('baz')
            ->andReturn($getResult);
        $redisMock->shouldReceive('close')
            ->andReturn($closeResult);

        $actual = $this->client->get('baz');

        $this->assertEquals($expected, $actual);
    }

    public function testGetSuccess_UsesNonStandardPort(): void
    {
        $expected = 'bar';

        $redisMock = $this->makeClientWithMock('tcp://barhost:6737');
        $redisMock->shouldReceive('connect')
            ->with('barhost', 6737)
            ->andReturn(TRUE);
        $redisMock->shouldReceive('get')
            ->with('baf')
            ->andReturn($expected);
        $redisMock->shouldReceive('close')
            ->andReturn(TRUE);

        $actual = $this->client->get('baf');

        $this->assertEquals($expected, $actual);
    }
}