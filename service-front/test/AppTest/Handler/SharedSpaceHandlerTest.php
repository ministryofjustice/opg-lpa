<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\SharedSpace\SharedSpaceForm;
use App\Handler\SharedSpaceHandler;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedSpaceHandlerTest extends TestCase
{
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&FormElementManager $formElementManager;
    private MockObject&SharedSpaceService $sharedSpaceService;
    private SharedSpaceForm $form;
    private SharedSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->form = new SharedSpaceForm();
        $this->form->init();

        $this->formElementManager->method('get')->with(SharedSpaceForm::class)->willReturn($this->form);

        $this->handler = new SharedSpaceHandler(
            $this->renderer,
            $this->formElementManager,
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
            [['invite' => 'sent'], false, true, false, false, false],
            [['invite' => 'revoked'], false, false, true, false, false],
            [['member' => 'deleted'], false, false, false, true, false],
            [['import' => 'success'], false, false, false, false, true],
            [[], true, false, false, false, false],
        ];
    }

    #[DataProvider('getShowsMembersAndInvitesProvider')]
    public function testGetShowsMembersAndInvites(array $query, bool $isAdmin, bool $inviteSuccess, bool $revokeSuccess, bool $memberDeleted, bool $importSuccess): void
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
                'form' => $this->form,
                'members' => $response['members'],
                'invites' => $response['invites'],
                'inviteSuccess' => $inviteSuccess,
                'revokeSuccess' => $revokeSuccess,
                'memberDeleted' => $memberDeleted,
                'importSuccess' => $importSuccess,
                'sharedSpaceName' => 'Example Shared Space',
                'signedInUserIsAdmin' => $isAdmin,
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'authError' => null,
            ])
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('GET')
            ->withQueryParams($query);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostImportsLpas(): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('import')
            ->with('someone@example.com', 'pass')
            ->willReturn(null);

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'someone@example.com',
                'password' => 'pass', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/shared-space?import=success', $response->getHeaderLine('Location'));
    }

    public function testPostImportsLpasWhenInvalidForm(): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembersAndInvites')
            ->willReturn([]);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/manage.twig', $this->callback(function ($data): bool {
                $this->assertEquals(['email' => ['cannot-be-empty'], 'password' => ['cannot-be-empty']], $this->form->getMessages());
                return true;
            }))
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('POST')
            ->withParsedBody([]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostImportsLpasWhenAlreadyInSharedSpace(): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('import')
            ->with('someone@example.com', 'pass')
            ->willReturn('user-already-in-space');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'someone@example.com',
                'password' => 'pass', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/shared-space/import-failed', $response->getHeaderLine('Location'));
    }

    public function testPostImportsLpasWhenAuthError(): void
    {
        $this->sharedSpaceService
            ->method('import')
            ->willReturn('account-not-active');
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('getMembersAndInvites')
            ->willReturn([]);

        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/manage.twig', $this->callback(function ($data): bool {
                $this->assertEquals('not-activated', $data['authError']);
                return true;
            }))
            ->willReturn('<html>manage shared space</html>');

        $request = (new ServerRequest())
            ->withAttribute(RequestAttribute::IDENTITY, new User('my-user', 'my-token', null, null))
            ->withMethod('POST')
            ->withParsedBody([
                'email' => 'someone@example.com',
                'password' => 'pass', // pragma: allowlist secret
            ]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
