<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginBackChannelLogoutHandler;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OneLoginBackChannelLogoutHandlerTest extends TestCase
{
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private OneLoginBackChannelLogoutHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->handler = new OneLoginBackChannelLogoutHandler($this->oneLoginService, $this->logger);
    }

    public function testReturns200WhenTheTokenIsAccepted(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->with('a.logout.token')
            ->willReturn(true);

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns400WhenTheTokenIsRejected(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.backchannel_logout_rejected', ['reason' => 'not_accepted']);

        $response = $this->handler->handle($this->request(['logout_token' => 'forged']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testDoesNotLogARejectionWhenTheTokenIsAccepted(): void
    {
        $this->oneLoginService->method('backChannelLogout')->willReturn(true);

        $this->logger->expects($this->never())->method('info');

        $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));
    }

    public function testReturns400WhenLogoutTokenIsMissing(): void
    {
        $this->oneLoginService->expects($this->never())->method('backChannelLogout');

        $response = $this->handler->handle($this->request([]));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns400WhenLogoutTokenIsBlank(): void
    {
        $this->oneLoginService->expects($this->never())->method('backChannelLogout');

        $response = $this->handler->handle($this->request(['logout_token' => '   ']));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testApiFailureIsReportedAsServerErrorNotBadRequest(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->willThrowException(new ApiException(new JsonResponse(['detail' => 'API unavailable'], 500)));

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
