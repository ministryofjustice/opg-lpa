<?php

declare(strict_types=1);

namespace ApplicationTest\Handle;

use Application\Handler\HealthCheckDependenciesHandler;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MakeShared\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class HealthCheckDependenciesHandlerTest extends TestCase
{
    private MockObject&ClientInterface $httpClient;
    private MockObject&CredentialsInterface $credentialProvider;
    private MockObject&SignatureV4 $signer;
    private MockObject&LoggerInterface $logger;

    private HealthCheckDependenciesHandler $handler;

    public function setUp(): void
    {
        $this->httpClient         = $this->createMock(ClientInterface::class);
        $this->credentialProvider = $this->createMock(CredentialsInterface::class);
        $this->signer             = $this->createMock(SignatureV4::class);
        $this->logger             = $this->createMock(LoggerInterface::class);

        $this->handler = new HealthCheckDependenciesHandler(
            $this->httpClient,
            $this->credentialProvider,
            $this->signer,
            $this->logger,
            [
                'onelogin' => ['discovery_url' => 'http://onelogin', 'user_agent' => 'us'],
                'processing-status' => ['endpoint' => 'http://status/v2/'],
            ],
        );
    }

    public function testItReturnsAllServiceOk(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->signer->expects($this->once())
            ->method('signRequest')
            ->with(
                $this->callback(fn (Request $r): bool => (string) $r->getUri() === 'http://status/v2/healthcheck'),
                $this->credentialProvider,
            )
            ->willReturnArgument(0);

        $this->httpClient->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (Request $r) use ($response): Response {
                return match ([$r->getMethod(), (string) $r->getUri(), $r->getHeader('User-Agent')]) {
                    ['GET', 'http://onelogin', ['us']] => $response,
                    ['GET', 'http://status/v2/healthcheck', []] => $response,
                    default => $this->fail('missing branch for: ' . $r->getMethod() . ' ' . $r->getUri()),
                };
            });

        $expectedResult = [
            'ok' => true,
            'status' => Constants::STATUS_PASS,
            'oneLogin' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => 200,
                ],
            ],
            'opg-data-lpa' => [
                'ok' => true,
                'status' => Constants::STATUS_PASS,
                'details' => [
                    'response_code' => 200,
                ],
            ],
        ];

        $request = $this->createMock(ServerRequestInterface::class);

        $result = $this->handler->handle($request);
        $this->assertEquals($expectedResult, json_decode($result->getBody()->getContents(), true));
    }
}
