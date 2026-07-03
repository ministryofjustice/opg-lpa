<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\FlashMessagesHolderMiddleware;
use App\Model\FlashMessagesHolder;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class FlashMessagesHolderMiddlewareTest extends TestCase
{
    private FlashMessagesHolder&MockObject $holder;

    protected function setUp(): void
    {
        $this->holder = $this->createMock(FlashMessagesHolder::class);
    }

    public function testProcessStoresFlashMessagesAndPassesThrough(): void
    {
        $flash = $this->createMock(FlashMessagesInterface::class);
        $request = (new ServerRequest())->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $flash);
        $expectedResponse = new PSR7Response();

        $this->holder->expects($this->once())
            ->method('set')
            ->with($flash);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new FlashMessagesHolderMiddleware($this->holder))->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessIgnoresMissingFlashMessages(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $this->holder->expects($this->never())
            ->method('set');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new FlashMessagesHolderMiddleware($this->holder))->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
