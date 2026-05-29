<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\AboutYouHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\UserDetails as UserService;
use App\View\Twig\FlashMessenger;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User as UserModel;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
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
    private FlashMessagesInterface&MockObject $flash;
    private AboutYouHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->userService = $this->createMock(UserService::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->flash = $this->createMock(FlashMessagesInterface::class);

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

    private function createUserWithName(): UserModel
    {
        $user = new UserModel([
            'id'        => '12345678901234567890123456789012',
            'createdAt' => '2020-01-01',
            'updatedAt' => '2020-01-02',
        ]);
        $user->name = new Name(['first' => 'Test', 'last' => 'User']);
        return $user;
    }

    private function createAuthenticatedSession(): SessionInterface&MockObject
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->with('identity')->willReturn(true);
        return $session;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?SessionInterface $session = null,
        ?UserModel $userDetails = null,
        bool $isNew = false,
    ): ServerRequest {
        $session = $session ?? $this->createAuthenticatedSession();
        $userDetails = $userDetails ?? $this->createUserWithName();

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash)
            ->withAttribute(RequestAttribute::USER_DETAILS, $userDetails)
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
        $response = $this->handler->handle(new ServerRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testGetRequestRendersForm(): void
    {
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

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUserWithoutNameIsRedirectedToNewRoute(): void
    {
        $userWithoutName = new UserModel([
            'id'        => '12345678901234567890123456789012',
            'createdAt' => '2020-01-01',
            'updatedAt' => '2020-01-02',
        ]);

        $response = $this->handler->handle($this->createRequest('GET', [], null, $userWithoutName));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/about-you/new', $response->getHeaderLine('Location'));
    }

    public function testNullUserDetailsIsRedirectedToNewRoute(): void
    {
        $response = $this->handler->handle($this->createRequest('GET', [], null, null));

        // createRequest sets a user with name by default; override with null
        $session = $this->createAuthenticatedSession();
        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->flash)
            ->withAttribute(RequestAttribute::USER_DETAILS, null)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/about-you/new', $response->getHeaderLine('Location'));
    }

    public function testGetNewRouteRendersIsNewTrue(): void
    {
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

        $response = $this->handler->handle($this->createRequest('GET', [], null, null, true));

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

        $this->flash->expects($this->once())
            ->method('flash')
            ->with(FlashMessenger::SUCCESS, ['Your details have been updated.']);

        $response = $this->handler->handle($this->createRequest('POST', $postData));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testPostValidNewUserDoesNotSetFlash(): void
    {
        $postData = ['name' => 'New User Name'];

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->userService->method('updateAllDetails');

        $this->flash->expects($this->never())->method('flash');

        $response = $this->handler->handle($this->createRequest('POST', $postData, null, null, true));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }
}
