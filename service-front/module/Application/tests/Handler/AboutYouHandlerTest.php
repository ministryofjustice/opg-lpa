<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\AboutYouHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as UserIdentity;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AboutYouHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private AuthenticationService&MockObject $authenticationService;
    private UserService&MockObject $userService;
    private SessionUtility&MockObject $sessionUtility;
    private FlashMessenger&MockObject $flashMessenger;
    private FormInterface&MockObject $form;
    private AboutYouHandler $handler;
    private User $user;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\User\AboutYou')
            ->willReturn($this->form);

        $this->handler = new AboutYouHandler(
            $this->renderer,
            $this->formElementManager,
            $this->authenticationService,
            $this->userService,
            $this->sessionUtility,
            $this->flashMessenger,
        );

        $this->user = $this->createUser();
    }

    private function createUser(): User
    {
        $name = new Name();
        $name->first = 'Test';
        $name->last = 'User';

        $dob = new Dob();
        $dob->date = new \DateTime('1957-12-17');

        $user = new User();
        $user->id = '12345678901234567890123456789012';
        $user->name = $name;
        $user->createdAt = new \DateTime('2020-01-01');
        $user->updatedAt = new \DateTime('2020-01-02');
        $user->dob = $dob;

        return $user;
    }

    private function createUserWithoutName(): User
    {
        $user = new User();
        $user->id = '12345678901234567890123456789012';
        $user->createdAt = new \DateTime('2020-01-01');
        $user->updatedAt = new \DateTime('2020-01-02');
        return $user;
    }

    private function getExpectedDataToSet(array $postData): array
    {
        $userDetails = $this->user->flatten();
        $existingData = array_intersect_key($userDetails, array_flip(['id', 'createdAt', 'updatedAt']));
        return array_merge($postData, $existingData);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testIndexActionGet(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you');

        $expectedUserData = $this->user->flatten();
        $expectedUserData['dob-date'] = [
            'day' => '17',
            'month' => '12',
            'year' => '1957',
        ];

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($userData) use ($expectedUserData) {
                $this->assertEquals($expectedUserData['dob-date'], $userData['dob-date']);
                return true;
            }));

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

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testIndexActionPostInvalid(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $postData = ['name' => ''];

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->getExpectedDataToSet($postData));

        $this->form->method('isValid')->willReturn(false);

        $this->userService->expects($this->never())->method('updateAllDetails');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/about-you/index.twig',
                $this->callback(fn($params) =>
                    $params['form'] === $this->form
                    && $params['isNew'] === false)
            )
            ->willReturn('<html>form with errors</html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody($postData);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testIndexActionPostValid(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $postData = ['name' => 'Updated Name'];

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->getExpectedDataToSet($postData));

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->userService
            ->expects($this->once())
            ->method('updateAllDetails')
            ->with($postData);

        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with('Your details have been updated.');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody($postData);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testNewActionGet(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $routeMatch = new RouteMatch(['new' => 'new']);
        $request = (new ServerRequest())->withAttribute(RouteMatch::class, $routeMatch);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you/new');

        $expectedUserData = $this->user->flatten();
        $expectedUserData['dob-date'] = [
            'day' => '17',
            'month' => '12',
            'year' => '1957',
        ];

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($userData) use ($expectedUserData) {
                $this->assertEquals($expectedUserData['dob-date'], $userData['dob-date']);
                return true;
            }));

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

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testNewActionPostValid(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $postData = ['name' => 'New User Name'];

        $routeMatch = new RouteMatch(['new' => 'new']);
        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody($postData)
            ->withAttribute(RouteMatch::class, $routeMatch);

        $this->form->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/user/about-you/new');

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn($postData);

        $this->userService
            ->expects($this->once())
            ->method('updateAllDetails')
            ->with($postData);

        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        // Flash message should NOT be shown for new users
        $this->flashMessenger->expects($this->never())->method('addSuccessMessage');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/user/dashboard', $response->getHeaderLine('Location'));
    }

    public function testUserWithoutNameIsRedirectedToNewRoute(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $userWithoutName = $this->createUserWithoutName();
        $this->userService->method('getUserDetails')->willReturn($userWithoutName);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/user/about-you/new', $response->getHeaderLine('Location'));
    }

    public function testUserWithoutNameOnNewRouteIsNotRedirected(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $userWithoutName = $this->createUserWithoutName();
        $this->userService->method('getUserDetails')->willReturn($userWithoutName);

        $routeMatch = new RouteMatch(['new' => 'new']);
        $request = (new ServerRequest())->withAttribute(RouteMatch::class, $routeMatch);

        $this->renderer->method('render')->willReturn('<html></html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUserWithoutDobDoesNotSetDobDate(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $name = new Name();
        $name->first = 'Test';
        $name->last = 'User';

        $userWithoutDob = new User();
        $userWithoutDob->id = '12345678901234567890123456789012';
        $userWithoutDob->name = $name;
        $userWithoutDob->createdAt = new \DateTime('2020-01-01');
        $userWithoutDob->updatedAt = new \DateTime('2020-01-02');
        $userWithoutDob->dob = null;

        $this->userService->method('getUserDetails')->willReturn($userWithoutDob);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($userData) {
                $this->assertArrayNotHasKey('dob-date', $userData);
                return true;
            }));

        $this->renderer->method('render')->willReturn('<html></html>');

        $request = new ServerRequest();
        $this->handler->handle($request);
    }

    public function testPostWithNullParsedBodyHandledSafely(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $this->form->expects($this->once())->method('setData');
        $this->form->method('isValid')->willReturn(false);
        $this->renderer->method('render')->willReturn('<html></html>');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSessionIsClearedAfterSuccessfulUpdate(): void
    {
        $this->authenticationService
            ->method('getIdentity')
            ->willReturn($this->createMock(UserIdentity::class));

        $this->userService->method('getUserDetails')->willReturn($this->user);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getData')->willReturn(['name' => 'Test']);

        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with(ContainerNamespace::USER_DETAILS, 'user');

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['name' => 'Test']);

        $this->handler->handle($request);
    }
}
