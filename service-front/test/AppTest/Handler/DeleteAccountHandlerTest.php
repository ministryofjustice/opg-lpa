<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Authentication\AuthenticationService;
use App\Handler\DeleteAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\SharedSpace\SharedSpaceService;
use App\Service\UserDetails;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;
    private SharedSpaceService&MockObject $sharedSpaceService;
    private UserDetails&MockObject $userService;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->userService = $this->createMock(UserDetails::class);
    }

    private function createRequest(): ServerRequest
    {
        $routeResult = $this->createMock(RouteResult::class);

        return new ServerRequest()
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token');
    }

    public static function featureEnabledProvider(): array
    {
        return [
            'shared spaces enabled' => [true, 5],
            'shared spaces disabled' => [false, null],
        ];
    }

    #[DataProvider('featureEnabledProvider')]
    public function testGetRendersDeleteAccountPageWithCommonTemplateVariables(bool $sharedSpacesEnabled, ?int $memberCount): void
    {
        if ($sharedSpacesEnabled) {
            $this->sharedSpaceService->expects($this->once())
                ->method('getMemberCount')
                ->willReturn($memberCount);
        }

        $handler = new DeleteAccountHandler(
            $this->renderer,
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->userService,
            $sharedSpacesEnabled
        );

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/delete/index.twig',
                [
                    'signedInUser' => null,
                    'secondsUntilSessionExpires' => null,
                    'lpa' => null,
                    'currentRouteName' => null,
                    'csrfToken' => 'test-token',
                    'memberCount' => $memberCount
                ]
            )
            ->willReturn('<html>delete account</html>');

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }

    public function testPostDeletesAccountAndRedirectsToDeletedPage(): void
    {
        $this->userService->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $handler = new DeleteAccountHandler(
            $this->renderer,
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->userService,
        );

        $response = $handler->handle($this->createRequest()->withMethod(RequestMethodInterface::METHOD_POST));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertEquals("/deleted", $response->getHeaderLine('Location'));
    }

    public function testPostRendersErrorOnDeleteFailure(): void
    {
        $this->userService->expects($this->once())
            ->method('delete')
            ->willReturn(false);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'error/500.twig',
                [
                    'signedInUser' => null,
                    'secondsUntilSessionExpires' => null,
                    'lpa' => null,
                    'currentRouteName' => null,
                    'csrfToken' => 'test-token',
                ]
            )
            ->willReturn('<html>delete account</html>');

        $handler = new DeleteAccountHandler(
            $this->renderer,
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->userService,
        );

        $response = $handler->handle($this->createRequest()->withMethod(RequestMethodInterface::METHOD_POST));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}
