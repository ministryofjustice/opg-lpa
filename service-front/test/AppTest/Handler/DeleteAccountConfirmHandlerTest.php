<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\DeleteAccountConfirmHandler;
use App\Middleware\RequestAttribute;
use App\Authentication\AuthenticationService;
use App\Service\UserDetails;
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
    private UserDetails&MockObject $userService;
    private DeleteAccountConfirmHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserDetails::class);

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
            ->withAttribute(RequestAttribute::USER_DETAILS, $user)
            ->withAttribute('secondsUntilSessionExpires', 3600);
    }

    public function testSuccessfulDeleteRedirectsToDeletedPage(): void
    {
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
                $this->callback(function (array $params) use ($user): bool {
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
