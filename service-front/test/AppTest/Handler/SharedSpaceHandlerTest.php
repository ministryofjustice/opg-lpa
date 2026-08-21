<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceHandler;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedSpaceHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private SharedSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->handler = new SharedSpaceHandler(
            $this->renderer,
            $this->sharedSpaceService,
        );
    }

    public function testGetShowsAboutWhenNotInSpace(): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembersAndInvites')
            ->willReturn(null);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/about.twig', [
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('GET');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function getShowsMembersAndInvitesProvider(): array
    {
        return [
            [['invite' => 'sent'], false, true, false, false],
            [['invite' => 'revoked'], false, false, true, false],
            [['member' => 'deleted'], false, false, false, true],
            [[], true, false, false, false],
        ];
    }

    #[DataProvider('getShowsMembersAndInvitesProvider')]
    public function testGetShowsMembersAndInvites(array $query, bool $isAdmin, bool $inviteSuccess, bool $revokeSuccess, bool $memberDeleted): void
    {
        $response = [
            'members' => [
                new SharedSpaceMember(['userId' => 'a-user', 'isAdmin' => false]),
                new SharedSpaceMember(['userId' => 'my-user', 'isAdmin' => $isAdmin]),
                new SharedSpaceMember(['userId' => 'another-user', 'isAdmin' => false]),
            ],
            'invites' => ['a' => 'b'],
        ];

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembersAndInvites')
            ->willReturn($response);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/manage.twig', [
                'members' => $response['members'],
                'invites' => $response['invites'],
                'inviteSuccess' => $inviteSuccess,
                'revokeSuccess' => $revokeSuccess,
                'memberDeleted' => $memberDeleted,
                'signedInUserIsAdmin' => $isAdmin,
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('GET')
            ->withQueryParams($query);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
