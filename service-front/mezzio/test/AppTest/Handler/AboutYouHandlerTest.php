<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\AboutYouHandler;
use App\Middleware\CsrfValidationMiddleware;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AboutYouHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private UserService&MockObject $userService;
    private FormInterface&MockObject $form;
    private SessionInterface&MockObject $session;
    private AboutYouHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\AboutYou')
            ->willReturn($this->form);

        $this->handler = new AboutYouHandler(
            $this->renderer,
            $this->formElementManager,
            $this->userService,
        );
    }

    private function createUserDetails(): array
    {
        return [
            'id' => '12345678901234567890123456789012',
            'createdAt' => '2020-01-01',
            'updatedAt' => '2020-01-02',
            'name' => ['first' => 'Test', 'last' => 'User'],
        ];
    }

    private function createAuthenticatedSession(array $userDetails = []): SessionInterface&MockObject
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')
            ->willReturnCallback(function (string $key) use ($userDetails): bool {
                return match ($key) {
                    'identity' => true,
                    'user_details' => !empty($userDetails),
                    default => false,
                };
            });
        $session->method('get')
            ->willReturnCallback(function (string $key) use ($userDetails) {
                return match ($key) {
                    'user_details' => $userDetails,
                    default => null,
                };
            });
        return $session;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?SessionInterface $session = null,
        bool $isNew = false,
    ): ServerRequest {
        $session = $session ?? $this->createAuthenticatedSession($this->createUserDetails());

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute('secondsUntilSessionExpires', 3600)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');

        if ($isNew) {
            $request = $request->withAttribute('new', 'new');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $unauthSession = $this->createMock(SessionInterface::class);
        $unauthSession->method('has')->willReturn(false);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $unauthSession);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testMissingSessionAttributeRedirectsToLogin(): void
    {
        $request = new ServerRequest();

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testGetRequestRendersForm(): void
    {
        $userDetails = $this->createUserDetails();

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/about-you/index.twig',
                $this->callback(fn($params) =>
                    $params['form'] === $this->form
                    && $params['isNew'] === false
                    && $params['cancelUrl'] === '/user/dashboard')
            )
            ->willReturn('<html>about you form</html>');

        $session = $this->createAuthenticatedSession($userDetails);
        $response = $this->handler->handle($this->createRequest('GET', [], $session));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUserWithoutNameIsRedirectedToNewRoute(): void
    {
        $userDetailsWithoutName = [
            'id' => '12345678901234567890123456789012',
            'createdAt' => '2020-01-01',
            'updatedAt' => '2020-01-02',
        ];

        $session = $this->createAuthenticatedSession($userDetailsWithoutName);
        $response = $this->handler->handle($this->createRequest('GET', [], $session));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/about-you/new', $response->getHeaderLine('Location'));
    }

    public function testGetNewRouteRendersIsNewTrue(): void
    {
        $userDetails = $this->createUserDetails();

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you/new');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/about-you/index.twig',
                $this->callback(fn($params) =>
                    $params['form'] === $this->form
                    && $params['isNew'] === true)
            )
            ->willReturn('<html>new user form</html>');

        $session = $this->createAuthenticatedSession($userDetails);
        $response = $this->handler->handle($this->createRequest('GET', [], $session, true));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->userService->expects($this->never())->method('updateAllDetails');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['name' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidUpdatesAndRedirects(): void
    {
        $postData = ['name' => 'Updated Name'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->userService
            ->expects($this->once())
            ->method('updateAllDetails')
            ->with($postData);

        $session = $this->createAuthenticatedSession($this->createUserDetails());
        $session->expects($this->once())->method('unset')->with('user_details');
        $session->expects($this->once())->method('set')->with('flash_success', ['Your details have been updated.']);

        $response = $this->handler->handle($this->createRequest('POST', $postData, $session));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testPostValidNewUserDoesNotSetFlash(): void
    {
        $postData = ['name' => 'New User Name'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->userService->method('updateAllDetails');

        $session = $this->createAuthenticatedSession($this->createUserDetails());
        $session->expects($this->never())->method('set');

        $response = $this->handler->handle($this->createRequest('POST', $postData, $session, true));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }
}
