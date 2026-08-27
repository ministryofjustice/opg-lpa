<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\CheckMemberStatusMiddleware;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class CheckMemberStatusMiddlewareTest extends TestCase
{
    private MockObject&SharedSpaceService $sharedSpaceService;
    private MockObject&TemplateRendererInterface $renderer;
    private CheckMemberStatusMiddleware $middleware;
    private PSR7Response $emptyResponse;

    protected function setUp(): void
    {
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->emptyResponse = new PSR7Response();

        $this->middleware = new CheckMemberStatusMiddleware(
            $this->sharedSpaceService,
            $this->renderer,
        );
    }

    public static function nonSharedSpaceRouteProvider()
    {
        return [['other.shared-space', 'shared-space.', 'shared-space2']];
    }

    public static function sharedSpaceRouteProvider()
    {
        return [['shared-space', 'shared-space.something']];
    }

    private function handler(?InvokedCount $count = null): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($count ?? $this->once())
            ->method('handle')
            // ->with($request)
            ->willReturn($this->emptyResponse);
        return $handler;
    }

    #[DataProvider('sharedSpaceRouteProvider')]
    public function testProcessWhenSharedSpaceMemberAndInactive(string $routeName): void
    {
        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: "1");
        $routeResult = RouteResult::fromRoute(new Route('/shared-space', $this->middleware, ['GET'], $routeName));

        $request = new ServerRequest()
            ->withAttribute(User::class, $identity)
            ->withAttribute(RouteResult::class, $routeResult);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/shared-space/suspended.twig');

        $this->sharedSpaceService->expects($this->once())
            ->method('getMember')
            ->with('1')
            ->willReturn(new SharedSpaceMember(['isActive' => false, 'sharedSpaceName' => 'My space']));

        $result = $this->middleware->process($request, $this->handler($this->never()));
        $this->assertInstanceOf(HtmlResponse::class, $result);
    }

    #[DataProvider('nonSharedSpaceRouteProvider')]
    public function testProcessWhenNonMatchingRoute(string $routeName): void
    {
        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: "1");
        $routeResult = RouteResult::fromRoute(new Route('/shared-space', $this->middleware, ['GET'], $routeName));

        $request = new ServerRequest()
            ->withAttribute(User::class, $identity)
            ->withAttribute(RouteResult::class, $routeResult);

        $result = $this->middleware->process($request, $this->handler());
        $this->assertSame($this->emptyResponse, $result);
    }

    public function testProcessWhenNotSharedSpaceMember(): void
    {
        $identity = new User('1', '', 1, new DateTime());
        $routeResult = RouteResult::fromRoute(new Route('/some-route', $this->middleware, ['GET'], 'shared-space'));

        $request = new ServerRequest()
            ->withAttribute(User::class, $identity)
            ->withAttribute(RouteResult::class, $routeResult);

        $result = $this->middleware->process($request, $this->handler());
        $this->assertSame($this->emptyResponse, $result);
    }

    public function testProcessWhenSharedSpaceMemberAndActive(): void
    {
        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: "1");
        $routeResult = RouteResult::fromRoute(new Route('/shared-space', $this->middleware, ['GET'], 'shared-space'));

        $request = new ServerRequest()
            ->withAttribute(User::class, $identity)
            ->withAttribute(RouteResult::class, $routeResult);

        $this->sharedSpaceService->expects($this->once())
            ->method('getMember')
            ->with('1')
            ->willReturn(new SharedSpaceMember(['isActive' => true]));

        $result = $this->middleware->process($request, $this->handler());
        $this->assertSame($this->emptyResponse, $result);
    }
}
