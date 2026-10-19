<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\RequestAttribute;
use App\Middleware\SharedSpaceAccessMiddleware;
use App\Model\Service\Authentication\Identity\User;
use App\Model\UserDetailsHolder;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\DataModel\User\User as SharedUser;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class SharedSpaceAccessMiddlewareTest extends TestCase
{
    private MockObject&SharedSpaceService $sharedSpaceService;
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&UserDetailsHolder $userDetailsHolder;
    private SharedSpaceAccessMiddleware $middleware;
    private PSR7Response $emptyResponse;

    protected function setUp(): void
    {
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->userDetailsHolder = $this->createMock(UserDetailsHolder::class);

        $this->emptyResponse = new PSR7Response();

        $this->middleware = new SharedSpaceAccessMiddleware(
            $this->sharedSpaceService,
            $this->renderer,
            $this->userDetailsHolder,
        );
    }

    public static function processProvider(): array
    {
        $nonSharedSpaceUser = new User('user-id', 'token', null, null, false, 'my-space');
        $sharedSpaceUser = new User('user-id', 'token', null, null, false, 'my-space');

        $invited = new SharedUser(['isInvited' => true]);
        $professional = new SharedUser(['isProfessional' => true]);

        $activeMember = new SharedSpaceMember(['isActive' => true, 'sharedSpaceName' => 'My Space']);
        $activeAdmin = new SharedSpaceMember(['isActive' => true, 'isAdmin' => true, 'sharedSpaceName' => 'My Space']);
        $suspendedMember = new SharedSpaceMember(['isActive' => false, 'sharedSpaceName' => 'My Space']);
        $suspendedAdmin = new SharedSpaceMember(['isActive' => false, 'isAdmin' => true, 'sharedSpaceName' => 'My Space']);

        return [
            'non-shared space route (null)' => [null, null, null, null, 'fallthrough'],
            'non-shared space route (empty)' => [[], null, null, null, 'fallthrough'],
            'suspended member' => [['blah'], $sharedSpaceUser, null, $suspendedMember, 'render'],
            'suspended admin' => [['blah'], $sharedSpaceUser, null, $suspendedAdmin, 'render'],

            'member with admin' => [['member'], $sharedSpaceUser, null, $activeAdmin, 'fallthrough'],
            'member with member' => [['member'], $sharedSpaceUser, null, $activeMember, 'fallthrough'],
            'member with professional' => [['member'], null, $professional, null, 'redirect'],
            'member with invited' => [['member'], null, $invited, null, 'redirect'],
            'member with non-shared space user' => [['member'], $nonSharedSpaceUser, null, null, 'redirect'],

            'admin with admin' => [['admin'], $sharedSpaceUser, null, $activeAdmin, 'fallthrough'],
            'admin with member' => [['admin'], $sharedSpaceUser, null, $activeMember, 'redirect-shared'],
            'admin with professional' => [['admin'], null, $professional, null, 'redirect'],
            'admin with invited' => [['admin'], null, $invited, null, 'redirect'],
            'admin with non-shared space user' => [['admin'], $nonSharedSpaceUser, null, null, 'redirect'],

            'professional with admin' => [['professional'], $sharedSpaceUser, null, $activeAdmin, 'redirect-shared'],
            'professional with member' => [['professional'], $sharedSpaceUser, null, $activeMember, 'redirect-shared'],
            'professional with professional' => [['professional'], null, $professional, null, 'fallthrough'],
            'professional with invited' => [['professional'], null, $invited, null, 'redirect'],
            'professional with non-shared space user' => [['professional'], $nonSharedSpaceUser, null, null, 'redirect'],

            'invited with admin' => [['invited'], $sharedSpaceUser, null, $activeAdmin, 'redirect-shared'],
            'invited with member' => [['invited'], $sharedSpaceUser, null, $activeMember, 'redirect-shared'],
            'invited with professional' => [['invited'], null, $professional, null, 'redirect'],
            'invited with invited' => [['invited'], null, $invited, null, 'fallthrough'],
            'invited with non-shared space user' => [['invited'], $nonSharedSpaceUser, null, null, 'redirect'],
        ];
    }

    #[DataProvider('processProvider')]
    public function testProcess(?array $sharedSpaceAllow, ?User $identity, ?SharedUser $details, ?SharedSpaceMember $member, string $expected): void
    {
        $route = new Route('/somewhere', $this->middleware, ['GET']);
        $route->setOptions(['sharedSpaceAllow' => $sharedSpaceAllow]);
        $routeResult = RouteResult::fromRoute($route);

        $request = new ServerRequest()
            ->withAttribute(RequestAttribute::IDENTITY, $identity)
            ->withAttribute(RouteResult::class, $routeResult);

        $handler = $this->createMock(RequestHandlerInterface::class);

        if ($details !== null) {
            $this->userDetailsHolder->expects($this->once())
                ->method('get')
                ->willReturn($details);
        }

        if ($member !== null) {
            $this->sharedSpaceService->expects($this->once())
                ->method('getMember')
                ->with($identity->id())
                ->willReturn($member);
        }

        switch ($expected) {
            case 'fallthrough':
                $handler->expects($this->once())
                ->method('handle')
                ->willReturn($this->emptyResponse);

                $result = $this->middleware->process($request, $handler);
                $this->assertSame($this->emptyResponse, $result);
                break;

            case 'render':
                $this->renderer->expects($this->once())
                ->method('render')
                ->with('application/authenticated/shared-space/suspended.twig');

                $result = $this->middleware->process($request, $handler);
                $this->assertInstanceOf(HtmlResponse::class, $result);
                break;

            case 'redirect':
                $result = $this->middleware->process($request, $handler);
                $this->assertInstanceOf(RedirectResponse::class, $result);
                $this->assertEquals('/user/dashboard', $result->getHeaderLine('Location'));
                break;

            case 'redirect-shared':
                $result = $this->middleware->process($request, $handler);
                $this->assertInstanceOf(RedirectResponse::class, $result);
                $this->assertEquals('/shared-space/dashboard', $result->getHeaderLine('Location'));
                break;
        }
    }
}
