<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Authentication\AuthenticationService;
use App\Form\SharedSpace\MakeSharedSpaceForm;
use App\Handler\MakeSharedSpaceHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MakeSharedSpaceHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private AuthenticationService&MockObject $authenticationService;
    private MakeSharedSpaceForm $form;
    private MakeSharedSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);

        $this->form = new MakeSharedSpaceForm();
        $this->form->init();

        $this->formElementManager->method('get')->with(MakeSharedSpaceForm::class)->willReturn($this->form);

        $this->handler = new MakeSharedSpaceHandler(
            $this->renderer,
            $this->formElementManager,
            $this->sharedSpaceService,
            $this->authenticationService,
        );
    }

    private function createRequest(): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');
    }

    public function testGetRequestDisplaysForm(): void
    {
        $this->renderer->method('render')->willReturn('<html>make shared space form</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function validDataProvider(): array
    {
        return [
            ['a'],
            [str_repeat('1', 100)],
        ];
    }

    #[DataProvider('validDataProvider')]
    public function testPostValidDataCreatesSharedSpaceRefreshesIdentityAndRedirects(string $name): void
    {
        $this->sharedSpaceService
            ->expects($this->once())
            ->method('create')
            ->with($name)
            ->willReturn('shared-space-1');

        $this->authenticationService
            ->expects($this->once())
            ->method('refreshSharedSpaceId')
            ->with('shared-space-1');

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody(['space-name' => $name]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/shared-space/created?space-name=My+family', $response->getHeaderLine('Location'));
    }

    public function testPostWhenCreationFailsShowsErrorAndDoesNotRefreshIdentity(): void
    {
        $this->sharedSpaceService->method('create')->willReturn(null);

        $this->authenticationService
            ->expects($this->never())
            ->method('refreshSharedSpaceId');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/make.twig',
                $this->callback(function ($params) {
                    return $params['error'] === 'Failed to create shared space. Please try again.';
                })
            )
            ->willReturn('<html>error</html>');

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody(['space-name' => 'My family']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function invalidDataProvider(): array
    {
        return [
            [''],
            [str_repeat('1', 101)],
        ];
    }

    #[DataProvider('invalidDataProvider')]
    public function testPostInvalidDataRedisplaysFormWithoutCreatingSharedSpace(string $name): void
    {
        $this->sharedSpaceService->expects($this->never())->method('create');
        $this->authenticationService->expects($this->never())->method('refreshSharedSpaceId');

        $this->renderer->method('render')->willReturn('<html>invalid</html>');

        $request = $this->createRequest()
            ->withMethod('POST')
            ->withParsedBody(['space-name' => $name]);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
