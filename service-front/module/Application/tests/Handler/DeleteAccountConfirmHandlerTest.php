<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\DeleteAccountConfirmHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAccountConfirmHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;
    private UserService&MockObject $userService;
    private DeleteAccountConfirmHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);

        $this->handler = new DeleteAccountConfirmHandler(
            $this->renderer,
            $this->authenticationService,
            $this->userService,
        );
    }

    private function createUserWithEmail(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->email = new EmailAddress(['address' => $email]);
        return $user;
    }

    private function createAuthenticatedRequest(User $user): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute('userDetails', $user)
            ->withAttribute('secondsUntilSessionExpires', 3600);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn(null);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testSuccessfulDeleteRedirectsToDeletedPage(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail();

        $this->userService
            ->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $request = $this->createAuthenticatedRequest($user);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/deleted', $response->getHeaderLine('Location'));
    }

    public function testFailedDeleteShowsErrorPage(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail();

        $this->userService
            ->expects($this->once())
            ->method('delete')
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'error/500.twig',
                $this->callback(function ($params) use ($user) {
                    return $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html>error page</html>');

        $request = $this->createAuthenticatedRequest($user);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
