<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserFind;
use App\Handler\UserFindHandler;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use PHPUnit\Framework\TestCase;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UserFindHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private LoggerInterface|MockObject $mockLogger;
    private UserFindHandler $handler;

    protected function setUp(): void
    {
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $_SESSION['jwt-payload'] =  ['csrf' => Common::TEST_CSRF_TOKEN];

        $this->handler = new UserFindHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    public function testRendersForm()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) => $args['form'] instanceof UserFind)
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testSubmitsSearch()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserFind::class);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([
                'query' => 'test',
                'offset' => '0',
                'secret' => $secret,
            ]);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with(['query' => 'test', 'offset' => '0', 'limit' => 11])
            ->willReturn([$user]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserFind
                && $args['form']->get('query')->getValue() === 'test'
                && $args['users'] === [$user])
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testAuditLogsSuccessfulFind()
    {
        $adminUser = new User([
            'id' => 'admin-id',
            'email' => new EmailAddress(['address' => 'admin@example.com']),
        ]);
        $foundUser = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserFind::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->willReturn([$foundUser]);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin searched users',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.find'
                    && $context['admin_id'] === 'admin-id'
                    && $context['admin_email'] === 'admin@example.com'
                    && $context['query'] === 'test'
                    && $context['results_count'] === 1)
            );

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([
                'query' => 'test',
                'offset' => '0',
                'secret' => $secret,
            ])
            ->withAttribute('user', $adminUser);

        $this->handler->handle($request);
    }

    public function testRequiresCsrf()
    {
        $secret = 'not_the_real_hash'; // pragma: allowlist secret

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([
                'query' => 'test',
                'offset' => '0',
                'secret' => $secret,
            ]);

        $this->mockUserService->expects($this->never())->method('match');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-find',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserFind
                && $args['form']->getMessages('secret') === [
                    'notSame' => 'The form submitted did not originate from the expected site'
                ]
                && $args['users'] === [])
        )->willReturn('response');

        $this->handler->handle($request);
    }
}
