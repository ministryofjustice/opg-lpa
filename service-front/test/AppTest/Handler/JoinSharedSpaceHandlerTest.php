<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Authentication\AuthenticationService;
use App\Form\SharedSpace\JoinSharedSpaceForm;
use App\Handler\JoinSharedSpaceHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
                $this->callback(fn(array $vars) => isset($vars['form']) && $vars['csrfToken'] === 'test-token' && $vars['isProfessional'] === true),
            )
            ->willReturn('<html>form</html>');

        $request = $this->createRequest('GET', [])
            ->withAttribute(
                RequestAttribute::USER_DETAILS,
                new User(['isProfessional' => true])
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public static function invalidFormProvider(): array
    {
        return [
            'empty' => ['', ''],
            'non-digit code' => ['My Space', '1234567X'],
            'too short code' => ['My Space', '1234567'],
            'too long code' => ['My Space', '123456789'],
        ];
    }

    #[DataProvider('invalidFormProvider')]
    public function testPostWithInvalidFormRendersForm(string $sharedSpaceName, string $accessCode): void
    {
        $request = $this->createRequest('POST', [
            'sharedSpaceName' => $sharedSpaceName,
            'sharedSpaceAccessCode' => $accessCode,
        ])
            ->withAttribute(
                RequestAttribute::USER_DETAILS,
                new User(['isProfessional' => true])
            );

        $this->renderer->method('render')
            ->with(
                'application/authenticated/shared-space/join.twig',
                $this->callback(fn(array $data) => isset($data['form']) && !$data['form']->isValid()),
            )
            ->willReturn('<html>form with errors</html>');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWithValidFormRedirectsToCorrectUrl(): void
    {
        $sharedSpaceId = 'my-space';
        $sharedSpaceName = 'My Space';
        $accessCode = '12345678';
        $email = 'user@example.com';

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode, $email)
            ->willReturn($sharedSpaceId);

        $this->authenticationService->method('refreshSharedSpaceId')
            ->with($sharedSpaceId);

        $request = $this->createRequest('POST', [
            'sharedSpaceName' => $sharedSpaceName,
            'sharedSpaceAccessCode' => $accessCode,
        ])
            ->withAttribute(
                RequestAttribute::USER_DETAILS,
                new User(['email' => new EmailAddress(['address' => $email])])
            );

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/shared-space/dashboard?joined=success', $location);
    }

    public function testPostWhenAlreadyInSharedSpace(): void
    {
        $sharedSpaceId = 'my-space';
        $sharedSpaceName = 'My Space';
        $accessCode = '12345678';
        $email = 'a@example.com';

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
                ->withAttribute(
                    RequestAttribute::USER_DETAILS,
                    new User(['email' => new EmailAddress(['address' => $email])])
                )
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/shared-space/dashboard', $location);
    }

    public function testPostWhenInviteNotFound(): void
    {
        $sharedSpaceName = 'My Space';
        $accessCode = '12345678';
        $email = 'a@example.com';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode([
            'detail' => 'invite-not-found'
        ]));

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getBody')->willReturn($stream);

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode, $email)
            ->willThrowException(new ApiException($errorResponse));

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/invite-not-found.twig',
                $this->callback(fn(array $vars) => $vars['email'] === $email),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'sharedSpaceName' => $sharedSpaceName,
                'sharedSpaceAccessCode' => $accessCode,
            ])
                ->withAttribute(
                    RequestAttribute::USER_DETAILS,
                    new User(['email' => new EmailAddress(['address' => $email])])
                )
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostWhenInviteExpired(): void
    {
        $sharedSpaceName = 'My Space';
        $accessCode = '12345678';
        $email = 'a@example.com';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode([
            'detail' => 'invite-expired',
            'expires' => '2024-01-02T12:13:14.000',
        ]));

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getBody')->willReturn($stream);

        $this->sharedSpaceService->method('join')
            ->with($sharedSpaceName, $accessCode, $email)
            ->willThrowException(new ApiException($errorResponse));

        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/invite-expired.twig',
                $this->callback(fn(array $vars) => $vars['inviteExpiry'] === '2 January 2024' && $vars['sharedSpaceName'] === $sharedSpaceName),
            )
            ->willReturn('<html>form</html>');

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'sharedSpaceName' => $sharedSpaceName,
                'sharedSpaceAccessCode' => $accessCode,
            ])
                ->withAttribute(
                    RequestAttribute::USER_DETAILS,
                    new User(['email' => new EmailAddress(['address' => $email])])
                )
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
