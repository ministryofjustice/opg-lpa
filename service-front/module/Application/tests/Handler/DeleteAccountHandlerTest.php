<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\DeleteAccountHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;
    private DeleteAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);

        $this->handler = new DeleteAccountHandler(
            $this->renderer,
            $this->authenticationService,
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

    public function testAuthenticatedUserSeesDeletePage(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $user = $this->createUserWithEmail();

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/delete/index.twig',
                $this->callback(function ($params) use ($user) {
                    return $params['signedInUser'] === $user
                        && $params['secondsUntilSessionExpires'] === 3600;
                })
            )
            ->willReturn('<html>delete page</html>');

        $request = $this->createAuthenticatedRequest($user);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
