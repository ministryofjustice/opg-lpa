<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginBackChannelLogoutHandler;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\SignedOutSubjects;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OneLoginBackChannelLogoutHandlerTest extends TestCase
{
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SignedOutSubjects&MockObject $signedOutSubjects;
    private OneLoginBackChannelLogoutHandler $handler;

    private const string SUB = 'urn:fdc:gov.uk:2022:abc';

    private const int SIGNED_OUT_AT = 1791283600;

    private const array SIGN_OUT = ['sub' => self::SUB, 'signedOutAt' => self::SIGNED_OUT_AT];

    protected function setUp(): void
    {
        parent::setUp();

        $this->oneLoginService   = $this->createMock(OneLoginService::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->signedOutSubjects = $this->createMock(SignedOutSubjects::class);

        $this->handler = new OneLoginBackChannelLogoutHandler(
            $this->oneLoginService,
            $this->signedOutSubjects,
            $this->logger,
        );
    }

    public function testReturns200WhenTheTokenIsAccepted(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->with('a.logout.token')
            ->willReturn(self::SIGN_OUT);

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * One Login confirmed they expect 200 even for a rejected token, the rejection is still logged.
     */
    public function testReturns200AndLogsWhenTheTokenIsRejected(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.backchannel_logout_rejected', ['reason' => 'not_accepted']);

        $response = $this->handler->handle($this->request(['logout_token' => 'forged']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testDoesNotLogARejectionWhenTheTokenIsAccepted(): void
    {
        $this->oneLoginService->method('backChannelLogout')->willReturn(self::SIGN_OUT);

        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('warning');

        $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));
    }

    public function testReturns200AndLogsWhenLogoutTokenIsMissing(): void
    {
        $this->oneLoginService->expects($this->never())->method('backChannelLogout');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('auth.onelogin.backchannel_logout_rejected', ['reason' => 'missing_logout_token']);

        $response = $this->handler->handle($this->request([]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReturns200AndLogsWhenLogoutTokenIsBlank(): void
    {
        $this->oneLoginService->expects($this->never())->method('backChannelLogout');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('auth.onelogin.backchannel_logout_rejected', ['reason' => 'missing_logout_token']);

        $response = $this->handler->handle($this->request(['logout_token' => '   ']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * Our own failure stays a 5xx so One Login's retry can still sign the user out.
     */
    public function testApiFailureIsReportedAsServerError(): void
    {
        $this->oneLoginService
            ->expects($this->once())
            ->method('backChannelLogout')
            ->willThrowException(new ApiException(new JsonResponse(['detail' => 'API unavailable'], 500)));

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testRecordsTheSignOutSoAPendingLinkCannotBeCompleted(): void
    {
        $this->oneLoginService->method('backChannelLogout')->willReturn(self::SIGN_OUT);

        $this->signedOutSubjects->expects($this->once())->method('recordSignOut')->with(self::SUB, self::SIGNED_OUT_AT);

        $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));
    }

    public function testRejectedTokenRecordsNoSignOut(): void
    {
        $this->oneLoginService->method('backChannelLogout')->willReturn(null);

        $this->signedOutSubjects->expects($this->never())->method('recordSignOut');

        $this->handler->handle($this->request(['logout_token' => 'forged']));
    }

    public function testFailingToRecordTheSignOutIsLoggedAndStillReturns200(): void
    {
        $this->oneLoginService->method('backChannelLogout')->willReturn(self::SIGN_OUT);
        $this->signedOutSubjects->method('recordSignOut')->willThrowException(new RuntimeException('Redis down'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('auth.onelogin.signed_out_record_failed', ['message' => 'Redis down']);

        $response = $this->handler->handle($this->request(['logout_token' => 'a.logout.token']));

        $this->assertSame(200, $response->getStatusCode());
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
