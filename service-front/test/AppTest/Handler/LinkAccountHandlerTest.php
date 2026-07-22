<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Authentication\AuthenticationService;
use App\Form\User\Login;
use App\Handler\LinkAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\UserDetails;
use Laminas\Authentication\Result;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private UserDetails&MockObject $userDetails;
    private Login $form;
    private LinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userDetails = $this->createMock(UserDetails::class);

        $this->form = new Login();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new LinkAccountHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->userDetails,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
    ): ServerRequest {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/link-account'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/linking/link-account.twig',
                $this->callback(fn(array $vars) => isset($vars['form']) && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormAndValidAuthSetsOneLoginAndRedirectsToDashboard(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';
        $oneLoginSub = 'TODO-get-the-current-one-login-sub';

        $this->authenticationService->method('setEmail')->with($email)->willReturn($this->authenticationService);
        $this->authenticationService->method('setPassword')->with($word)->willReturn($this->authenticationService);
        $this->authenticationService->method('authenticate')->willReturn(new Result(1, null));

        $this->userDetails->method('setOneLoginSub')->with($oneLoginSub)->willReturn(true);

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/user/dashboard', $location);
    }

    public function testPostWithValidFormAndInvalidAuthRendersForm(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';

        $this->authenticationService->method('setEmail')->with($email)->willReturn($this->authenticationService);
        $this->authenticationService->method('setPassword')->with($word)->willReturn($this->authenticationService);
        $this->authenticationService->method('authenticate')->willReturn(new Result(0, null));

        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormAndValidAuthCannotSetSubRendersForm(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';

        $this->authenticationService->method('setEmail')->with($email)->willReturn($this->authenticationService);
        $this->authenticationService->method('setPassword')->with($word)->willReturn($this->authenticationService);
        $this->authenticationService->method('authenticate')->willReturn(new Result(1, null));

        $this->userDetails->method('setOneLoginSub')->willReturn(false);

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
