<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\ResendMemberInviteHandler;
use App\Middleware\RequestAttribute;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\User\User;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResendMemberInviteHandlerTest extends TestCase
{
    private SharedSpaceService&MockObject $sharedSpaceService;
    private ResendMemberInviteHandler $handler;
    private User $inviter;

    protected function setUp(): void
    {
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->handler = new ResendMemberInviteHandler($this->sharedSpaceService);
        $this->inviter = new User(['id' => 'admin-1']);
    }

    private function createRequest(): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['invite-id' => '5']);

        return (new ServerRequest())
            ->withMethod('POST')
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(RequestAttribute::USER_DETAILS, $this->inviter);
    }

    public function testRedirectsWithInviteeNameWhenInviteIsResent(): void
    {
        $this->sharedSpaceService->expects($this->once())
            ->method('resendInvite')
            ->with($this->inviter, '5')
            ->willReturn('Alt Person');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/shared-space?invite=resent&invitedMemberName=Alt+Person',
            $response->getHeaderLine('Location'),
        );
    }

    public function testRedirectsWithFailureWhenResendFails(): void
    {
        $this->sharedSpaceService->method('resendInvite')->willReturn(null);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space?invite=resend-failed', $response->getHeaderLine('Location'));
    }
}
