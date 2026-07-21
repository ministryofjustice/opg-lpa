<?php

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\DiscoveryDocumentFetcher;
use Application\Model\Service\OneLogin\Service;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ServiceTest extends MockeryTestCase
{
    private Service $service;
    private MockInterface|DiscoveryDocumentFetcher $fetcher;
    private string $authEndpoint = 'https://auth.example.com/authorize';

    public function setUp(): void
    {
        $logger = Mockery::spy(LoggerInterface::class);

        $this->fetcher = Mockery::mock(DiscoveryDocumentFetcher::class);
        $this->fetcher->shouldReceive('authorizationEndpoint')
            ->andReturn($this->authEndpoint)
            ->byDefault();

        $this->service = new Service();
        $this->service->setLogger($logger);
        $this->service->setConfig(['onelogin' => ['client_id' => 'test-client-id']]);
        $this->service->setDiscoveryDocumentFetcher($this->fetcher);
    }

    public function testCreateAuthenticationRequestReturnsExpectedParams(): void
    {
        $seededBytes = $this->seedRandomBytes();
        $this->service->setRandomByteGenerator($seededBytes);

        $redirectUrl = 'https://example.com/auth/redirect';
        $result      = $this->service->createAuthenticationRequest($redirectUrl);

        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('nonce', $result);
        $this->assertArrayHasKey('url', $result);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{24}$/', $result['state']);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['nonce']);

        // Parse the built URL query and assert every required parameter
        $urlParts = parse_url($result['url']);
        parse_str($urlParts['query'], $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame($redirectUrl, $query['redirect_uri']);
        $this->assertSame('openid email', $query['scope']);
        $this->assertSame($result['state'], $query['state']);
        $this->assertSame($result['nonce'], $query['nonce']);
        $this->assertSame('["Cl.Cm"]', $query['vtr']);
    }

    public function testStateAndNonceMatchThoseEmbeddedInUrl(): void
    {
        $result = $this->service->createAuthenticationRequest('https://example.com/auth/redirect');

        $urlParts = parse_url($result['url']);
        parse_str($urlParts['query'], $query);

        $this->assertSame($result['state'], $query['state']);
        $this->assertSame($result['nonce'], $query['nonce']);
    }

    public function testTwoCallsProduceDifferentStateAndNonce(): void
    {
        $first  = $this->service->createAuthenticationRequest('https://example.com/auth/redirect');
        $second = $this->service->createAuthenticationRequest('https://example.com/auth/redirect');

        $this->assertNotSame($first['state'], $second['state']);
        $this->assertNotSame($first['nonce'], $second['nonce']);
    }

    public function testMissingClientIdThrows(): void
    {
        $this->service->setConfig(['onelogin' => ['client_id' => null]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('onelogin.client_id');

        $this->service->createAuthenticationRequest('https://example.com/auth/redirect');
    }

    public function testMissingFetcherThrows(): void
    {
        $service = new Service();
        $service->setLogger(Mockery::spy(LoggerInterface::class));
        $service->setConfig(['onelogin' => ['client_id' => 'test-client-id']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DiscoveryDocumentFetcher');

        $service->createAuthenticationRequest('https://example.com/auth/redirect');
    }

    /**
     * Returns a callable seam that produces deterministic bytes for testing.
     *
     * @return callable(int): string
     */
    private function seedRandomBytes(): callable
    {
        $call = 0;

        return static function (int $length) use (&$call): string {
            $call++;
            return str_repeat(chr($call * 3), $length);
        };
    }
}
