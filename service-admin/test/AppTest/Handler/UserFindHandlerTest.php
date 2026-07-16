<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserFind;
use App\Handler\UserFindHandler;
use App\RequestAttributes;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
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

        $this->handler = new UserFindHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    private function makeRequest(string $method, array $queryParams = [], string $adminEmail = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN);

        if ($adminEmail !== null) {
            $request = $request->withAttribute(RequestAttributes::USER_EMAIL, $adminEmail);
        }

        return $request;
    }

    public function testRendersForm()
    {
        $request = $this->makeRequest(RequestMethodInterface::METHOD_GET);

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

        $request = $this->makeRequest(RequestMethodInterface::METHOD_GET, [
            'query' => 'test',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com');

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
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserFind::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->willReturn([new User(['name' => new Name(['first' => 'David'])])]);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin searched users',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.find'
                    && $context['admin_email'] === 'admin@example.com'
                    && !array_key_exists('admin_id', $context)
                    && $context['query'] === 'test'
                    && $context['results_count'] === 1)
            );

        $request = $this->makeRequest(RequestMethodInterface::METHOD_GET, [
            'query' => 'test',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com');

        $this->handler->handle($request);
    }

    public function testRequiresCsrf()
    {
        $request = $this->makeRequest(RequestMethodInterface::METHOD_GET, [
            'query' => 'test',
            'offset' => '0',
            'secret' => 'not_the_real_hash', // pragma: allowlist secret
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
