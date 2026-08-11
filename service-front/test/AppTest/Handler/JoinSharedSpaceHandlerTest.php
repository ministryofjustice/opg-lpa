<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Authentication\AuthenticationService;
use App\Form\SharedSpace\JoinSharedSpaceForm;
use App\Handler\JoinSharedSpaceHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class JoinSharedSpaceHandlerTest extends TestCase
{
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&FormElementManager $formElementManager;
    private MockObject&SharedSpaceService $sharedSpaceService;
    private MockObject&AuthenticationService $authenticationService;
    private JoinSharedSpaceForm $form;
    private JoinSharedSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);

        $this->form = new JoinSharedSpaceForm();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new JoinSharedSpaceHandler(
            $this->renderer,
            $this->formElementManager,
            $this->sharedSpaceService,
            $this->authenticationService,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
    ): ServerRequest {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/join'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/join.twig',
                $this->callback(fn(array $vars) => isset($vars['form']) && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithInvalidFormRendersForm(): void
    {
        $this->renderer->method('render')->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormRedirectsToCorrectUrl(): void
    {
        $sharedSpaceId = 'my-space';
        $sharedSpaceName = 'My Space';
        $accessCode = '1234';

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode)
            ->willReturn($sharedSpaceId);

        $this->authenticationService->method('refreshSharedSpaceId')
            ->with($sharedSpaceId);

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'sharedSpaceName' => $sharedSpaceName,
                'sharedSpaceAccessCode' => $accessCode,
            ])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/shared-space/dashboard', $location);
    }

    public function testPostWhenAlreadyInSharedSpace(): void
    {
        $sharedSpaceId = 'my-space';
        $sharedSpaceName = 'My Space';
        $accessCode = '1234';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode([
            'detail' => 'user-already-in-shared-space',
            'sharedSpaceId' => $sharedSpaceId,
        ]));

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getBody')->willReturn($stream);

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode)
            ->willThrowException(new ApiException($errorResponse));

        $this->authenticationService->method('refreshSharedSpaceId')
            ->with($sharedSpaceId);

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'sharedSpaceName' => $sharedSpaceName,
                'sharedSpaceAccessCode' => $accessCode,
            ])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/shared-space/dashboard', $location);
    }

    public function testPostWhenInviteNotFound(): void
    {
        $sharedSpaceName = 'My Space';
        $accessCode = '1234';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode([
            'detail' => 'invite-not-found'
        ]));

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getBody')->willReturn($stream);

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode)
            ->willThrowException(new ApiException($errorResponse));

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/join.twig',
                $this->callback(fn(array $vars) => isset($vars['form'])
                                && $vars['csrfToken'] === 'test-token'
                                && $vars['joinError'] === 'The shared space name and/or access code are incorrect'),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'sharedSpaceName' => $sharedSpaceName,
                'sharedSpaceAccessCode' => $accessCode,
            ])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
