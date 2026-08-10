<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Authentication\AuthenticationService;
use App\Form\User\Login;
use App\Handler\LinkAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\UserDetails;
use Laminas\Authentication\Result;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LinkAccountHandlerTest extends TestCase
{
    private const string PENDING_SUB = 'urn:fdc:gov.uk:2022:newuser';

    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private UserDetails&MockObject $userDetails;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private Login $form;
    private LinkAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer              = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager    = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userDetails           = $this->createMock(UserDetails::class);
        $this->logger                = $this->createMock(LoggerInterface::class);
        $this->session               = $this->createMock(SessionInterface::class);

        $this->form = new Login();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new LinkAccountHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->userDetails,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?string $pendingSub = self::PENDING_SUB,
    ): ServerRequest {
        $pendingLink = $pendingSub === null
            ? null
            : ['sub' => $pendingSub, 'email' => 'newuser@example.com'];

        $this->session
            ->method('get')
            ->willReturnCallback(fn(string $key) => $key === 'onelogin_pending_link' ? $pendingLink : null);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/link-account'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function stubAuthentication(string $email, string $password, bool $valid): void
    {
        $this->authenticationService->method('setEmail')->with($email)->willReturn($this->authenticationService);
        $this->authenticationService->method('setPassword')->with($password)->willReturn($this->authenticationService);
        $this->authenticationService->method('authenticate')->willReturn(new Result($valid ? 1 : 0, null));
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

    public function testMissingPendingSubRedirectsToLogin(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.link_missing_pending_sub');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormAndValidAuthLinksSessionSubAndRedirectsToDashboard(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';

        $this->stubAuthentication($email, $word, true);

        $this->userDetails->expects($this->once())
            ->method('setOneLoginSub')
            ->with(self::PENDING_SUB)
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('unset')
            ->with('onelogin_pending_link');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testPostWithValidFormAndInvalidAuthRendersForm(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';

        $this->stubAuthentication($email, $word, false);

        $this->userDetails->expects($this->never())->method('setOneLoginSub');
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidAuthPassesAuthErrorToTemplateSoUserIsAdvised(): void
    {
        $email = 'my.email@example.com';
        $word  = 'guessable';

        $this->stubAuthentication($email, $word, false);

        $this->userDetails->expects($this->never())->method('setOneLoginSub');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/linking/link-account.twig',
                $this->callback(fn(array $vars) => ($vars['authError'] ?? null) === 'authentication-failed'),
            )
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormAndValidAuthCannotSetSubRendersForm(): void
    {
        $email = 'my.email@example.com';
        $word = 'guessable';

        $this->stubAuthentication($email, $word, true);

        $this->userDetails->method('setOneLoginSub')->willReturn(false);

        $this->session->expects($this->never())->method('unset');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('auth.onelogin.link_persist_failed');

        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['email' => $email, 'password' => $word])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
