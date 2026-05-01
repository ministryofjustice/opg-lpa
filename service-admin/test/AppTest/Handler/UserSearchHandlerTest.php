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
            ->withParsedBody(['email' => 'user@example.com', 'secret' => $secret]);

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
                    && $context['admin_email'] === 'admin@example.com'
                    && $context['searched_for'] === 'user@example.com')
            );

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody(['email' => 'user@example.com', 'secret' => $secret])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testRendersErrorWhenUserNotFound()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
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
            ->withParsedBody(['email' => 'user@example.com', 'secret' => $secret]);

        $this->handler->handle($request);
    }

    public function testRequiresCsrf()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
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
            ->withParsedBody(['email' => 'user@example.com', 'secret' => $secret]);

        $this->handler->handle($request);
    }
}
