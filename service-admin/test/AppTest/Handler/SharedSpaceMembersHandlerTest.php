<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceMembersHandler;
use App\RequestAttributes;
use App\Service\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SharedSpaceMembersHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private SharedSpaceService|MockObject $mockSharedSpaceService;
    private LoggerInterface|MockObject $mockLogger;
    private SharedSpaceMembersHandler $handler;

    protected function setUp(): void
    {
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockSharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->handler = new SharedSpaceMembersHandler($this->mockSharedSpaceService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    public function testReturnsNotFoundWhenNoSharedSpaceFound(): void
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('sharedSpaceId', '123');

        $this->mockSharedSpaceService->expects($this->once())
            ->method('members')
            ->with('123', 1, 20, 1, 20)
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::shared-space-members',
            $this->callback(fn ($args) =>
                $args['sharedSpaceId'] === '123'
                && $args['failureReason'] === 'No shared space found')
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testReturnsMembersAndInvites(): void
    {
        $members = [
            ['userId' => 'u1', 'name' => ['first' => 'Alice', 'last' => 'A'], 'email' => 'alice@example.com', 'isAdmin' => true],
        ];
        $invites = [
            ['id' => 1, 'fullName' => 'Bob B', 'email' => 'bob@example.com', 'createdAt' => '2024-01-01T00:00:00.000000+0000', 'isExpired' => false],
        ];

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('sharedSpaceId', '123')
            ->withAttribute(RequestAttributes::USER_EMAIL, 'admin@example.com');

        $this->mockSharedSpaceService->expects($this->once())
            ->method('members')
            ->with('123', 1, 20, 1, 20)
            ->willReturn([
                'sharedSpaceName' => 'The Space',
                'members' => $members,
                'membersTotal' => 1,
                'invites' => $invites,
                'invitesTotal' => 1,
            ]);

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed shared space members',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.shared-space.members.view'
                    && $context['admin_email'] === 'admin@example.com'
                    && $context['shared_space_id'] === '123'
                    && $context['member_count'] === 1
                    && $context['invite_count'] === 1)
            );

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::shared-space-members',
            $this->callback(fn ($args) =>
                $args['sharedSpaceName'] === 'The Space'
                && $args['members'] === $members
                && $args['invites'] === $invites
                && $args['membersPaginator']->getTotal() === 1
                && $args['invitesPaginator']->getTotal() === 1)
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPaginatesMembersAndInvitesIndependently(): void
    {
        $members = array_fill(0, 20, ['userId' => 'u', 'name' => ['first' => 'A', 'last' => 'B'], 'email' => 'a@example.com', 'isAdmin' => false]);
        $invites = array_fill(0, 5, ['id' => 1, 'fullName' => 'C D', 'email' => 'c@example.com', 'createdAt' => '2024-01-01T00:00:00.000000+0000', 'isExpired' => false]);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('sharedSpaceId', '123')
            ->withQueryParams(['membersPage' => '2', 'invitesPage' => '1']);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('members')
            ->with('123', 2, 20, 1, 20)
            ->willReturn([
                'sharedSpaceName' => 'The Space',
                'members' => $members,
                'membersTotal' => 45,
                'invites' => $invites,
                'invitesTotal' => 5,
            ]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::shared-space-members',
            $this->callback(fn ($args) =>
                $args['membersPaginator']->getPage() === 2
                && $args['membersPaginator']->getNextPage() === 3
                && $args['membersPaginator']->getPreviousPage() === 1
                && $args['invitesPaginator']->getPage() === 1
                && $args['invitesPaginator']->getNextPage() === null
                && $args['invitesPaginator']->getPreviousPage() === null)
        )->willReturn('response');

        $this->handler->handle($request);
    }
}
