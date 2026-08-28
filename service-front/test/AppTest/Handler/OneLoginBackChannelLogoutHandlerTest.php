<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginBackChannelLogoutHandler;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class OneLoginBackChannelLogoutHandlerTest extends MockeryTestCase
{
    private MockInterface|OneLoginService $oneLoginService;
    private MockInterface|LoggerInterface $logger;
    private OneLoginBackChannelLogoutHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->oneLoginService = Mockery::mock(OneLoginService::class);
        $this->logger          = Mockery::spy(LoggerInterface::class);

        $this->handler = new OneLoginBackChannelLogoutHandler($this->oneLoginService, $this->logger);
    }

    public function testReturns200WhenTheTokenIsAccepted(): void
    {
        $this->oneLoginService->shouldReceive('backChannelLogout')
            ->once()->with('a.logout.token')->andReturn(true);

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns400WhenTheTokenIsRejected(): void
    {
        $this->oneLoginService->shouldReceive('backChannelLogout')
            ->once()->andReturn(false);

        $response = $this->handler->handle($this->request(['logout_token' => 'forged']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns400WhenLogoutTokenIsMissing(): void
    {
        $this->oneLoginService->shouldNotReceive('backChannelLogout');

        $response = $this->handler->handle($this->request([]));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns400WhenLogoutTokenIsBlank(): void
    {
        $this->oneLoginService->shouldNotReceive('backChannelLogout');

        $response = $this->handler->handle($this->request(['logout_token' => '   ']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testApiFailureIsReportedAsServerErrorNotBadRequest(): void
    {
        $this->oneLoginService->shouldReceive('backChannelLogout')
            ->once()
            ->andThrow(new ApiException(new JsonResponse(['detail' => 'API unavailable'], 500)));

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(502, $response->getStatusCode());
        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    private function request(array $body): ServerRequest
    {
        return (new ServerRequest(
            serverParams: [],
            uploadedFiles: [],
            uri: '/auth/onelogin/backchannel-logout',
            method: 'POST',
            headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
        ))->withParsedBody($body);
    }
}
