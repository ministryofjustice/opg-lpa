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
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
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
            [['outcome' => 'sent', 'name' => 'John Smith'], [], false, true, false, false, false, 'John Smith', false, null],
            [['outcome' => 'revoked', 'name' => 'John Smith'], [], false, false, true, false, false, 'John Smith', false, null],
            [['outcome' => 'resent', 'name' => 'John Smith'], [], false, false, false, true, false, 'John Smith', false, null],
            [null, ['member' => 'deleted'], false, false, false, false, true, null, false, null],
            [null, ['member' => 'updated', 'updatedMemberName' => 'Jane Doe'], false, false, false, false, false, null, true, 'Jane Doe'],
            [null, ['invite' => 'sent', 'invitedMemberName' => 'John Smith'], false, false, false, false, false, null, false, null],
            [null, [], true, false, false, false, false, null, false, null],
        ];
    }

    #[DataProvider('getShowsMembersAndInvitesProvider')]
    public function testGetShowsMembersAndInvites(?array $inviteOutcome, array $query, bool $isAdmin, bool $inviteSuccess, bool $revokeSuccess, bool $resendSuccess, bool $memberDeleted, ?string $invitedMemberName, bool $memberUpdated, ?string $updatedMemberName): void
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
                'resendSuccess' => $resendSuccess,
                'memberDeleted' => $memberDeleted,
                'memberUpdated' => $memberUpdated,
                'updatedMemberName' => $updatedMemberName,
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

        $flash = $this->createMock(FlashMessagesInterface::class);
        $flash->method('getFlash')
            ->with(SharedSpaceHandler::INVITE_OUTCOME_FLASH)
            ->willReturn($inviteOutcome);

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash)
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

        $flash = $this->createMock(FlashMessagesInterface::class);
        $flash->expects($this->once())
            ->method('flash')
            ->with(SharedSpaceHandler::INVITE_OUTCOME_FLASH, ['outcome' => 'resent', 'name' => 'a b']);

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash)
            ->withMethod('POST')
            ->withParsedBody(['resend' => '5']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/shared-space', $response->getHeaderLine('location'));
    }

    public function testPostShowsErrorWhenResentInviteEmailFails(): void
    {
        $user = new SharedUser();

        $this->sharedSpaceService->method('resendInvite')->willReturn(null);
        $this->sharedSpaceService->method('getMembersAndInvites')->willReturn([
            'members' => [new SharedSpaceMember(['userId' => 'my-user', 'isAdmin' => true])],
            'invites' => [],
            'name' => 'Example Shared Space',
        ]);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/manage.twig',
                $this->callback(fn (array $vars) => $vars['error'] === 'An error occurred while sending the invitation. Please try again later.'
                    && $vars['resendSuccess'] === false),
            )
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withMethod('POST')
            ->withParsedBody(['resend' => '5']);

        $this->assertInstanceOf(HtmlResponse::class, $this->handler->handle($request));
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
                'resendSuccess' => false,
                'memberDeleted' => false,
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => true,
                'signedInUser' => $user,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'invitedMemberName' => null,
                'memberUpdated' => false,
                'updatedMemberName' => null,
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
