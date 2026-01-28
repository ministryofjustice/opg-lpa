<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\UserLpasHandler;
use App\Service\User\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;

class UserLpasHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private UserLpasHandler $handler;

    protected function setUp(): void
    {
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockUserService = $this->createMock(UserService::class);

        $_SESSION['jwt-payload'] = ['csrf' => Common::TEST_CSRF_TOKEN];

        $this->handler = new UserLpasHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
    }

    public function testReturnsMissingEmailError()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('id', '123')
            ->withQueryParams([]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-lpas',
            $this->callback(fn ($args) =>
                $args['userId'] === '123'
                && $args['failureReason'] === 'missing-email')
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testReturnsNoLpasError()
    {
        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('id', '123')
            ->withQueryParams(['email' => 'user@example.com']);

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123')
            ->willReturn(false);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-lpas',
            $this->callback(fn ($args) =>
                $args['userId'] === '123'
                && $args['failureReason'] === 'no-lpas')
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

        $request = new ServerRequest()
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withAttribute('id', '123')
            ->withQueryParams(['email' => 'user@example.com']);

        $this->mockUserService->expects($this->once())
            ->method('userLpas')
            ->with('123')
            ->willReturn($lpas);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-lpas',
            $this->callback(fn ($args) =>
                $args['lpaEmail'] === 'user@example.com'
                && $args['lpas'] === $lpas)
        )->willReturn('response');

        $response = $this->handler->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
