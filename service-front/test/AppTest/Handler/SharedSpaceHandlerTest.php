<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceHandler;
use App\Middleware\RequestAttribute;
use App\Service\SharedSpace\SharedSpaceService;
use App\View\Twig\FlashMessenger;
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

    private function userDetails(?string $sharedSpaceId): SharedUser
    {
        return (new SharedUser())->setId('my-user')->setSharedSpaceId($sharedSpaceId);
    }

    public function testGetShowsAboutWhenNotInSpace(): void
    {
        $userDetails = $this->userDetails(null);

        $this->sharedSpaceService
            ->expects($this->never())
            ->method('getMembersAndInvites')
            ->willReturn(null);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/about.twig', [
                'signedInUser' => $userDetails,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = new ServerRequest()
            ->withAttribute(RequestAttribute::USER_DETAILS, $userDetails)
            ->withMethod('GET');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function getShowsMembersAndInvitesProvider(): array
    {
        return [
            [['member' => 'updated', 'updatedMemberName' => 'Jane Doe'], false, true, 'Jane Doe'],
            [[], true, false, null],
        ];
    }

    #[DataProvider('getShowsMembersAndInvitesProvider')]
    public function testGetShowsMembersAndInvites(array $query, bool $isAdmin, bool $memberUpdated, ?string $updatedMemberName): void
    {
        $userDetails = $this->userDetails('shared-space-id');

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
                'memberUpdated' => $memberUpdated,
                'updatedMemberName' => $updatedMemberName,
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => $isAdmin,
                'signedInUser' => $userDetails,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'error' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = new ServerRequest()
            ->withAttribute(RequestAttribute::USER_DETAILS, $userDetails)
            ->withMethod('GET')
            ->withQueryParams($query);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostResendsInvite(): void
    {
        $user = $this->userDetails('shared-space-id');

        $this->sharedSpaceService
            ->expects($this->once())
            ->method('resendInvite')
            ->with($user, 5)
            ->willReturn('a b');

        $flash = $this->createMock(FlashMessagesInterface::class);
        $flash->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::SUCCESS, ['The invite has been resent to a b.']);

        $request = new ServerRequest()
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
        $user = $this->userDetails('shared-space-id');

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
                $this->callback(fn (array $vars) => $vars['error'] === 'An error occurred while sending the invitation. Please try again later.'),
            )
            ->willReturn('<html>manage shared space</html>');

        $request = new ServerRequest()
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
        $user = $this->userDetails('shared-space-id');

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
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => true,
                'signedInUser' => $user,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'memberUpdated' => false,
                'updatedMemberName' => null,
                'error' => $expectedError,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = new ServerRequest()
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withMethod('POST')
            ->withParsedBody(['resend' => '5']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
