<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceHandler;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\DataModel\User\User as SharedUser;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedSpaceHandlerTest extends TestCase
{
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&SharedSpaceService $sharedSpaceService;
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
                'csrfToken' => null
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
            [['invite' => 'sent', 'invitedMemberName' => 'John Smith'], false, true, false, false, 'John Smith'],
            [['invite' => 'revoked'], false, false, true, false, null],
            [['member' => 'deleted'], false, false, false, true, null],
            [[], true, false, false, false, null],
        ];
    }

    #[DataProvider('getShowsMembersAndInvitesProvider')]
    public function testGetShowsMembersAndInvites(array $query, bool $isAdmin, bool $inviteSuccess, bool $revokeSuccess, bool $memberDeleted, ?string $invitedMemberName): void
    {
        $response = [
            'members' => [
                new SharedSpaceMember(['userId' => 'a-user', 'isAdmin' => false]),
                new SharedSpaceMember(['userId' => 'my-user', 'isAdmin' => $isAdmin]),
                new SharedSpaceMember(['userId' => 'another-user', 'isAdmin' => false]),
            ],
            'invites' => ['a' => 'b'],
            'name' => 'Example Shared Space',
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
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => $isAdmin,
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'invitedMemberName' => $invitedMemberName,
                'error' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('GET')
            ->withQueryParams($query);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostResendsInvite(): void
    {
        $user = new SharedUser();

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('resendInvite')
            ->with($user, 5)
            ->willReturn('a b');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withMethod('POST')
            ->withParsedBody(['resend' => '5']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/shared-space?invite=sent&invitedMemberName=a+b', $response->getHeaderLine('location'));
    }

    public static function postWhenResendErrorsProvider(): array
    {
        return [
            ['user-already-in-shared-space', 'Email address belongs to an active member of this shared space -– enter a different email address'],
            ['invite-already-exists', 'Email address is for a pending member of this shared space – enter a different one or cancel the previous invite'],
            ['blah', 'An error occurred while sending the invitation. Please try again later.'],
        ];
    }

    #[DataProvider('postWhenResendErrorsProvider')]
    public function testPostWhenResendErrors(string $errorCode, string $expectedError): void
    {
        $user = new SharedUser();

        $response = [
            'members' => [
                new SharedSpaceMember(['userId' => 'a-user', 'isAdmin' => false]),
                new SharedSpaceMember(['userId' => 'my-user', 'isAdmin' => true]),
                new SharedSpaceMember(['userId' => 'another-user', 'isAdmin' => false]),
            ],
            'invites' => ['a' => 'b'],
            'name' => 'Example Shared Space',
        ];

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('resendInvite')
            ->with($user, 5)
            ->willThrowException(new \RuntimeException($errorCode));
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembersAndInvites')
            ->willReturn($response);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/manage.twig', [
                'members' => $response['members'],
                'invites' => $response['invites'],
                'inviteSuccess' => false,
                'revokeSuccess' => false,
                'memberDeleted' => false,
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => true,
                'signedInUser' => $user,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'invitedMemberName' => null,
                'error' => $expectedError,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withMethod('POST')
            ->withParsedBody(['resend' => '5']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
