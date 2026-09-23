<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\DeleteSharedSpaceMemberHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteSharedSpaceMemberHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private DeleteSharedSpaceMemberHandler $handler;
    private SharedSpaceMember $member;

    private const MEMBER_ID = 'member-1';

    protected function setUp(): void
    {
        $this->member = new SharedSpaceMember([
            'name'    => ['first' => 'Member', 'last' => 'One'],
            'email'   => 'member1@example.com',
            'isAdmin' => true,
            'isActive' => true,
            'createdAt' => new \DateTime('2024-01-01T00:00:00Z'),
            'sharedSpaceId' => 'shared-space-1',
            'userId' => 'user-1',
            'sharedSpaceName' => 'Test Shared Space',
        ]);

        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->handler = new DeleteSharedSpaceMemberHandler(
            $this->renderer,
            $this->sharedSpaceService,
        );
    }

    private function createRequest(string $memberId = self::MEMBER_ID): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['member-id' => $memberId]);

        return (new ServerRequest())
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');
    }

    public function testGetRequestDisplaysPageForExistingMember(): void
    {
        $this->sharedSpaceService->method('getMember')->with(self::MEMBER_ID)->willReturn($this->member);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/delete-member.twig', [
                'member' => $this->member,
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => 'test-token',
                'error' => null,
            ])
            ->willReturn('<html>member</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRequestRedirectsWhenMemberNotFound(): void
    {
        $this->sharedSpaceService->method('getMember')->willReturn(null);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space', $response->getHeaderLine('Location'));
    }

    public function testPostDeletesMemberAndRedirects(): void
    {
        $this->sharedSpaceService->method('getMember')->willReturn($this->member);

        $this->sharedSpaceService
            ->method('deleteMember')
            ->with(self::MEMBER_ID)
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest()->withMethod('POST'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space?member=deleted', $response->getHeaderLine('Location'));
    }

    public function testPostDeletesMemberWhenError(): void
    {
        $this->sharedSpaceService->method('getMember')->willReturn($this->member);

        $this->sharedSpaceService
            ->method('deleteMember')
            ->with(self::MEMBER_ID)
            ->willReturn(false);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/delete-member.twig', [
                'member' => $this->member,
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => 'test-token',
                'error' => 'Failed to delete member. Please try again.',
            ])
            ->willReturn('<html>member</html>');

        $response = $this->handler->handle($this->createRequest()->withMethod('POST'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
