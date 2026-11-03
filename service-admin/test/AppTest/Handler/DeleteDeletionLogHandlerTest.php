<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\DeleteDeletionLog;
use App\Handler\DeleteDeletionLogHandler;
use App\RequestAttributes;
use App\Service\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteDeletionLogHandlerTest extends TestCase
{
    private const string EMAIL = 'user@example.com';

    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private UrlHelper|MockObject $mockUrlHelper;
    private LoggerInterface|MockObject $mockLogger;
    private FlashMessagesInterface|MockObject $mockFlash;
    private DeleteDeletionLogHandler $handler;

    protected function setUp(): void
    {
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockUrlHelper = $this->createMock(UrlHelper::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockFlash = $this->createMock(FlashMessagesInterface::class);

        $this->mockUrlHelper->method('generate')->willReturnCallback(
            fn (string $route) => '/' . $route
        );

        $this->handler = new DeleteDeletionLogHandler($this->mockUserService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setUrlHelper($this->mockUrlHelper);
        $this->handler->setLogger($this->mockLogger);
    }

    private function makeRequest(string $method, array $query = [], array $body = []): ServerRequest
    {
        return (new ServerRequest())
            ->withMethod($method)
            ->withQueryParams($query)
            ->withParsedBody($body)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN)
            ->withAttribute(RequestAttributes::USER_EMAIL, 'admin@example.com')
            ->withAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE, $this->mockFlash);
    }

    private function validBody(string $email = self::EMAIL): array
    {
        return [
            'email'  => $email,
            'secret' => hash('sha512', Common::TEST_CSRF_TOKEN . DeleteDeletionLog::class),
        ];
    }

    public function testGetRendersConfirmationPage(): void
    {
        $this->mockUserService->expects($this->never())->method('deleteDeletionLog');

        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with(
                'app::delete-deletion-log',
                $this->callback(
                    fn ($args) => $args['form'] instanceof DeleteDeletionLog
                        && $args['email'] === self::EMAIL
                        && $args['error'] === null
                )
            )->willReturn('response');

        $response = $this->handler->handle(
            $this->makeRequest(RequestMethodInterface::METHOD_GET, ['email' => self::EMAIL])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithoutEmailRedirectsToSearch(): void
    {
        $this->mockTemplateRenderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->makeRequest(RequestMethodInterface::METHOD_GET));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/search', $response->getHeaderLine('Location'));
    }

    public function testPostDeletesLogAndRedirectsToSearchWithSuccessFlash(): void
    {
        $this->mockUserService->expects($this->once())
            ->method('deleteDeletionLog')
            ->with(self::EMAIL)
            ->willReturn(true);

        $this->mockFlash->expects($this->once())
            ->method('flash')
            ->with('success', 'Deletion log deleted for ' . self::EMAIL);

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin deleted deletion log',
                $this->callback(
                    fn ($context) => $context['event'] === 'admin.deletion_log.deleted'
                        && $context['admin_email'] === 'admin@example.com'
                )
            );

        $response = $this->handler->handle(
            $this->makeRequest(RequestMethodInterface::METHOD_POST, [], $this->validBody())
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/search', $response->getHeaderLine('Location'));
    }

    public function testPostRerendersWithErrorWhenDeleteFails(): void
    {
        $this->mockUserService->expects($this->once())
            ->method('deleteDeletionLog')
            ->with(self::EMAIL)
            ->willReturn(false);

        $this->mockFlash->expects($this->never())->method('flash');

        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with(
                'app::delete-deletion-log',
                $this->callback(fn ($args) => $args['error'] !== null && $args['email'] === self::EMAIL)
            )->willReturn('response');

        $response = $this->handler->handle(
            $this->makeRequest(RequestMethodInterface::METHOD_POST, [], $this->validBody())
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithInvalidCsrfDoesNotDelete(): void
    {
        $this->mockUserService->expects($this->never())->method('deleteDeletionLog');
        $this->mockFlash->expects($this->never())->method('flash');

        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with('app::delete-deletion-log', $this->anything())
            ->willReturn('response');

        $response = $this->handler->handle($this->makeRequest(
            RequestMethodInterface::METHOD_POST,
            [],
            ['email' => self::EMAIL, 'secret' => 'not-the-token']
        ));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithoutEmailRedirectsToSearch(): void
    {
        $this->mockUserService->expects($this->never())->method('deleteDeletionLog');

        $response = $this->handler->handle($this->makeRequest(
            RequestMethodInterface::METHOD_POST,
            [],
            ['secret' => $this->validBody()['secret']]
        ));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/search', $response->getHeaderLine('Location'));
    }
}
