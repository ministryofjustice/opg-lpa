<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpasHandler;
use App\RequestAttributes;
use App\Service\Paginator;
use App\Service\UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LpasHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private LoggerInterface|MockObject $mockLogger;
    private LpasHandler $handler;

    protected function setUp(): void
    {
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->handler = new LpasHandler($this->mockUserService, new Paginator());
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    public function testReturnsMissingUserIdAndSharedSpaceIdError()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams([]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['userId'] === null
                && $args['failureReason'] === 'A user email or shared space name must be provided')
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testReturnsNoLpasError()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('userId', '123')
            ->withQueryParams(['email' => 'user@example.com']);

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123', 1, 20)
            ->willReturn(false);

        // No audit log fires on failure
        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['userId'] === '123'
                && $args['failureReason'] === 'No LPAs found')
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testReturnsUserLpas()
    {
        $lpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
            ['uId' => 'M-9876-5432-1098', 'donor' => 'Jane Smith'],
        ];
        $adminUser = new User(['id' => 'admin-id']);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('userId', '123')
            ->withAttribute('user', $adminUser)
            ->withQueryParams(['email' => 'user@example.com']);

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123', 1, 20)
            ->willReturn(['results' => $lpas, 'total' => 2]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['lpasOwner'] === 'user@example.com'
                && $args['lpas'] === $lpas
                && $args['paginator']->getNextPage() === null
                && $args['paginator']->getPreviousPage() === null)
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturnsSharedSpaceLpas()
    {
        $lpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
            ['uId' => 'M-9876-5432-1098', 'donor' => 'Jane Smith'],
        ];
        $adminUser = new User(['id' => 'admin-id']);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('sharedSpaceId', '123')
            ->withAttribute('user', $adminUser)
            ->withQueryParams(['sharedSpaceName' => 'Shared Space']);

        $this->mockUserService->expects($this->once())
            ->method('sharedSpaceLpas')
            ->with('123', 1, 20)
            ->willReturn(['results' => $lpas, 'total' => 2]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['lpasOwner'] === 'Shared Space'
                && $args['lpas'] === $lpas
                && $args['paginator']->getNextPage() === null
                && $args['paginator']->getPreviousPage() === null)
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPaginatesUserLpasAndSetsNextPage()
    {
        $lpas = array_fill(0, 20, ['uId' => 'M-1234-5678-9012']);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('userId', '123')
            ->withQueryParams(['email' => 'user@example.com', 'page' => '1']);

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123', 1, 20)
            ->willReturn(['results' => $lpas, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['paginator']->getNextPage() === 2
                && $args['paginator']->getPreviousPage() === null
                && $args['routeName'] === 'user.lpas'
                && $args['routeParams'] === ['userId' => '123']
                && $args['queryParams'] === ['email' => 'user@example.com'])
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testPaginatesSharedSpaceLpasAndSetsPreviousPage()
    {
        $lpas = array_fill(0, 5, ['uId' => 'M-1234-5678-9012']);

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('sharedSpaceId', '123')
            ->withQueryParams(['sharedSpaceName' => 'Shared Space', 'page' => '2']);

        $this->mockUserService->expects($this->once())
            ->method('sharedSpaceLpas')
            ->with('123', 2, 20)
            ->willReturn(['results' => $lpas, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::view-lpas',
            $this->callback(fn ($args) =>
                $args['paginator']->getNextPage() === null
                && $args['paginator']->getPreviousPage() === 1
                && $args['routeName'] === 'shared-space.lpas'
                && $args['routeParams'] === ['sharedSpaceId' => '123']
                && $args['queryParams'] === ['sharedSpaceName' => 'Shared Space'])
        )->willReturn('response');

        $this->handler->handle($request);
    }

    public function testAuditLogsUserLpasView()
    {
        $lpas = [['uId' => 'M-1234-5678-9012']];

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123', 1, 20)
            ->willReturn(['results' => $lpas, 'total' => 1]);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed user LPAs',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.lpas.view'
                    && $context['admin_email'] === 'admin@example.com'
                    && !array_key_exists('admin_id', $context)
                    && !array_key_exists('viewed_user_email', $context)
                    && $context['viewed_user'] === '123'
                    && $context['lpa_count'] === 1)
            );

        $request = (new ServerRequest())
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('userId', '123')
            ->withAttribute(RequestAttributes::USER_EMAIL, 'admin@example.com')
            ->withQueryParams(['email' => 'user@example.com']);

        $this->handler->handle($request);
    }
}
