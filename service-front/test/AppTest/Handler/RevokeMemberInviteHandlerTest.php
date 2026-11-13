<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\RevokeMemberInviteHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RevokeMemberInviteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private RevokeMemberInviteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->sharedSpaceService->method('getMembersAndInvites')->willReturn([
            'members' => [],
            'invites' => [
                ['id' => 4, 'fullName' => 'Other Person', 'email' => 'other@example.com', 'isExpired' => false],
                ['id' => 5, 'fullName' => 'Alt Person', 'email' => 'alt@example.com', 'isExpired' => false],
            ],
            'name' => 'My Space',
        ]);

        $this->handler = new RevokeMemberInviteHandler(
            $this->renderer,
            $this->sharedSpaceService,
            $this->createMock(FormElementManager::class),
        );
    }

    private function createRequest(string $method, string $inviteId = '5'): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['invite-id' => $inviteId]);

        return (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');
    }

    public function testGetShowsInviteeNameAndEmail(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/shared-space/revoke-invite.twig', [
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => 'test-token',
                'inviteeName' => 'Alt Person',
                'inviteeEmail' => 'alt@example.com',
            ])
            ->willReturn('<html>revoke</html>');

        $response = $this->handler->handle($this->createRequest('GET'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRedirectsToMembersPageWhenInviteIsNotInSharedSpace(): void
    {
        $this->sharedSpaceService->expects($this->never())->method('revokeInvite');

        $response = $this->handler->handle($this->createRequest('POST', '99'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space', $response->getHeaderLine('Location'));
    }

    public function testPostRevokesInviteAndRedirectsWithInviteeName(): void
    {
        $this->sharedSpaceService->expects($this->once())
            ->method('revokeInvite')
            ->with('5')
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/shared-space?invite=revoked&invitedMemberName=Alt+Person',
            $response->getHeaderLine('Location'),
        );
    }

    public function testPostShowsPageAgainWhenRevokeFails(): void
    {
        $this->sharedSpaceService->method('revokeInvite')->willReturn(false);
        $this->renderer->method('render')->willReturn('<html>revoke</html>');

        $response = $this->handler->handle($this->createRequest('POST'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
