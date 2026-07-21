<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserSearch;
use App\Handler\UserSearchHandler;
use App\RequestAttributes;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
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

        $this->handler = new UserSearchHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    private function makeGetRequest(array $queryParams = []): ServerRequest
    {
        return (new ServerRequest())
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN);
    }

    private function makePostRequest(array $body, string $adminEmail = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod(RequestMethodInterface::METHOD_POST)
            ->withParsedBody($body)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN);

        if ($adminEmail !== null) {
            $request = $request->withAttribute(RequestAttributes::USER_EMAIL, $adminEmail);
        }

        return $request;
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

        $this->handler->handle($this->makeGetRequest(['email' => 'user@example.com']));
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret],
            'admin@example.com'
        ));
    }

    public function testSubmitsSearchByUserId()
    {
        $user = ['userId' => 'abc123', 'isActive' => true];
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'abc123', 'searchType' => 'userId', 'secret' => $secret],
            'admin@example.com'
        ));
    }

    public function testSubmitsSearchByAReference()
    {
        $user = ['userId' => 'abc123def456', 'isActive' => true];
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'A-99998888882', 'searchType' => 'aReference', 'secret' => $secret],
            'admin@example.com'
        ));
    }

    public function testAuditLogsSuccessfulSearch()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn(new User(['name' => new Name(['first' => 'David'])]));

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed user data',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.search'
                    && $context['admin_email'] === 'admin@example.com'
                    && !array_key_exists('admin_id', $context)
                    && $context['searched_for'] === 'user@example.com')
            );

        $this->handler->handle($this->makePostRequest(
            ['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret],
            'admin@example.com'
        ));
    }

    public function testRendersErrorWhenUserNotFound()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('search')
            ->with('user@example.com')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('email') === ['No user found for email address']
                && $args['user'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makePostRequest(
            ['email' => 'user@example.com', 'searchType' => 'email', 'secret' => $secret]
        ));
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'abc123', 'searchType' => 'userId', 'secret' => $secret]
        ));
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'A-99998888882', 'searchType' => 'aReference', 'secret' => $secret]
        ));
    }

    public function testRequiresCsrf()
    {
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

        $this->handler->handle($this->makePostRequest(
            ['email' => 'user@example.com', 'searchType' => 'email', 'secret' => 'not_the_real_hash'] // pragma: allowlist secret
        ));
    }
}
