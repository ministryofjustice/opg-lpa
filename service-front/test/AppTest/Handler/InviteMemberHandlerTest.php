<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa;

use App\Form\SharedSpace\InviteMemberForm;
use App\Handler\InviteMemberHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\User\User;
use MakeShared\DataModel\Common\EmailAddress;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InviteMemberHandlerTest extends TestCase
{
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&FormElementManager $formElementManager;
    private MockObject&SharedSpaceService $sharedSpaceService;
    private InviteMemberForm $form;
    private InviteMemberHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);

        $this->form = new InviteMemberForm();
        $this->form->init();

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new InviteMemberHandler(
            $this->renderer,
            $this->formElementManager,
            $this->sharedSpaceService,
        );
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
    ): ServerRequest {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/invite-member'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(RequestAttribute::USER_DETAILS, new User([
                'email'     => new EmailAddress(['address' => 'me@example.com']),
            ]));

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
                'application/authenticated/shared-space/invite.twig',
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
        $this->sharedSpaceService->method('invite')
            ->willReturn(true);

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'firstNames' => 'a',
                'lastName' => 'b',
                'email' => 'me@example.com',
                'permissions' => '1',
            ])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaderLine('Location');
        $this->assertEquals('/shared-space?invite=sent', $location);
    }


    public static function sharedServiceErrorProvider(): array
    {
        return [
            ['user-already-in-shared-space', 'This email address is already part of the shared space'],
            ['invite-already-exists', 'This email address has already been invited to the shared space'],
        ];
    }

    #[DataProvider('sharedServiceErrorProvider')]
    public function testPostWithExistingInviteResourceUpdatesFormErrorMessage(string $errorCode, string $expectedMessage): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/invite.twig',
                $this->callback(fn(array $vars) => $vars['form']->getMessages()['email'][0] === $expectedMessage),
            )
            ->willReturn('<html>form</html>');

        $this->sharedSpaceService
            ->method('invite')
            ->willThrowException(new ApiException(new TextResponse('[]', StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY), $errorCode));

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'firstNames' => 'a',
                'lastName' => 'b',
                'email' => 'me@example.com',
                'permissions' => '1',
            ])
        );

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testPostWithUnexpectedErrorSetsErrorInRender(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/shared-space/invite.twig',
                $this->callback(fn(array $vars) => $vars['error'] === 'An error occurred while sending the invitation. Please try again later.'),
            )
            ->willReturn('<html>form</html>');

        $this->sharedSpaceService
            ->method('invite')
            ->willThrowException(new ApiException(new TextResponse('[]', StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY), 'some-error'));

        $response = $this->handler->handle(
            $this->createRequest('POST', [
                'firstNames' => 'a',
                'lastName' => 'b',
                'email' => 'me@example.com',
                'permissions' => '1',
            ])
        );

        $this->assertInstanceOf(Response::class, $response);
    }
}
