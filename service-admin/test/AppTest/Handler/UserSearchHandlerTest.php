<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserSearch;
use App\Handler\UserSearchHandler;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserSearchHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private LoggerInterface|MockObject $mockLogger;
    private UserSearchHandler $handler;

    protected function setUp(): void
    {
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $_SESSION['jwt-payload'] =  ['csrf' => Common::TEST_CSRF_TOKEN];

        $this->handler = new UserSearchHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    public function testRendersForm()
    {
        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with(
                'app::user-search',
                $this->callback(fn ($args) =>
                    $args['form'] instanceof UserSearch
                    && $args['form']->get('email')->getValue() === 'user@example.com')
            )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams(['email' => 'user@example.com']);

        $this->handler->handle($request);
    }

    public function testSubmitsSearch()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
        $adminUser = new User(['id' => 'admin-id']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->get('email')->getValue() === 'user@example.com'
                && $args['user'] === $user)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testSubmitsSearchByUserId()
    {
        $user = ['userId' => 'abc123', 'isActive' => true];
        $adminUser = new User(['id' => 'admin-id']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['user'] === $user)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'abc123', 'searchType' => 'userId', 'secret' => $secret])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testSubmitsSearchByAReference()
    {
        $user = ['userId' => 'abc123def456', 'isActive' => true];
        $adminUser = new User(['id' => 'admin-id']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['user'] === $user)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'A-99998888882', 'searchType' => 'aReference', 'secret' => $secret])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testAuditLogsSuccessfulSearch()
    {
        $adminUser = new User([
            'id' => 'admin-id',
            'email' => new EmailAddress(['address' => 'admin@example.com']),
        ]);
        $foundUser = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn($foundUser);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed user data',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.search'
                    && $context['admin_id'] === 'admin-id'
                    && !array_key_exists('admin_email', $context)
                    && $context['searched_for'] === 'user@example.com')
            );

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testRendersErrorWhenUserNotFound()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn(false);

        // No audit log fires when no user is found
        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('email') === ['No user found for email address']
                && $args['user'] === null)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret]);

        $this->handler->handle($request);
    }

    public function testRendersErrorWhenUserNotFoundByUserId()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('email') === ['No user found for user ID']
                && $args['user'] === null)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'abc123', 'searchType' => 'userId', 'secret' => $secret]);

        $this->handler->handle($request);
    }

    public function testRendersErrorWhenUserNotFoundByAReference()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('email') === ['No user found for A Reference']
                && $args['user'] === null)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'A-99998888882', 'searchType' => 'aReference', 'secret' => $secret]);

        $this->handler->handle($request);
    }

    public function testRequiresCsrf()
    {
        $secret = 'not_the_real_hash'; // pragma: allowlist secret

        $this->mockUserService->expects($this->never())->method('search');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('secret') === [
                    'notSame' => 'The form submitted did not originate from the expected site'
                ]
                && $args['user'] === null)
        )->willReturn('response');

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret]);

        $this->handler->handle($request);
    }
}
