<?php

namespace ApplicationTest\Model\Service\OneLogin;

use Application\Model\Service\OneLogin\DiscoveryDocumentFetchException;
use Application\Model\Service\OneLogin\DiscoveryDocumentFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DiscoveryDocumentFetcherTest extends MockeryTestCase
{
    private function buildFetcher(array $responses, string $discoveryUrl = 'https://oidc.example.com/.well-known/openid-configuration'): DiscoveryDocumentFetcher
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        return new DiscoveryDocumentFetcher($client, $discoveryUrl);
    }

    public function testHappyPathReturnsAuthorizationEndpoint(): void
    {
        $document = json_encode([
            'authorization_endpoint' => 'https://auth.example.com/authorize',
            'issuer'                 => 'https://oidc.example.com',
        ]);

        $fetcher = $this->buildFetcher([
            new Response(200, [], $document),
        ]);

        $endpoint = $fetcher->authorizationEndpoint();

        $this->assertSame('https://auth.example.com/authorize', $endpoint);
    }

    public function testNon200ResponseThrows(): void
    {
        $fetcher = $this->buildFetcher([
            new Response(503, [], 'Service Unavailable'),
        ]);

        $this->expectException(DiscoveryDocumentFetchException::class);
        $this->expectExceptionMessage('503');

        $fetcher->authorizationEndpoint();
    }

    public function testMalformedJsonThrows(): void
    {
        $fetcher = $this->buildFetcher([
            new Response(200, [], 'not-json'),
        ]);

        $this->expectException(DiscoveryDocumentFetchException::class);
        $this->expectExceptionMessage('malformed JSON');

        $fetcher->authorizationEndpoint();
    }

    public function testMissingAuthorizationEndpointThrows(): void
    {
        $document = json_encode(['issuer' => 'https://oidc.example.com']);

        $fetcher = $this->buildFetcher([
            new Response(200, [], $document),
        ]);

        $this->expectException(DiscoveryDocumentFetchException::class);
        $this->expectExceptionMessage('authorization_endpoint');

        $fetcher->authorizationEndpoint();
    }
}
