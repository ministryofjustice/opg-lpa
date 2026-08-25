<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\CheckMemberStatusMiddleware;
use App\Authentication\AuthenticationService;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class CheckMemberStatusMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private UrlHelper&MockObject $urlHelper;
    private CheckMemberStatusMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->middleware = new CheckMemberStatusMiddleware(
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->urlHelper,
        );
    }

    public function testProcessWhenNotSharedSpaceMember(): void
    {
        $identity = new User('1', '', 1, new DateTime());
        $request = new ServerRequest()->withAttribute(User::class, $identity);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessWhenSharedSpaceMemberAndActive(): void
    {
        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: "1");
        $request = new ServerRequest()->withAttribute(User::class, $identity);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $this->sharedSpaceService->expects($this->once())->method('getMember')->with('1')->willReturn(new SharedSpaceMember(['isActive' => true]));

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessWhenSharedSpaceMemberAndInactive(): void
    {
        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: "1");

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('clear');
        $session->expects($this->once())->method('regenerate');

        $request = new ServerRequest()
            ->withAttribute(User::class, $identity)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->sharedSpaceService->expects($this->once())->method('getMember')->with('1')->willReturn(new SharedSpaceMember(['isActive' => false]));

        $this->authenticationService->expects($this->once())->method('clearIdentity');

        $this->urlHelper->expects($this->once())->method('generate')->with('application.login', ['state' => 'member-suspended'])->willReturn('/expected-url');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/expected-url', $result->getHeaderLine('Location'));
    }
}
