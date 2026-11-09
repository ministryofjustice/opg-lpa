<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Authentication\AuthenticationService;
use App\Handler\DeleteAccountHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\RequestAttribute;
use App\Model\Service\Authentication\Identity\User;
use App\Service\SharedSpace\SharedSpaceService;
use App\Service\UserDetails;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use DateTime;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
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
            'shared spaces enabled' => [true, 5, false, null],
            'shared spaces disabled' => [false, null, false, null],
            'one login, shared space with other members' => [true, 5, true, 'My space'],
            'one login, last member of a shared space' => [true, 1, true, null],
        ];
    }

    #[DataProvider('featureEnabledProvider')]
    public function testGetRendersDeleteAccountPageWithCommonTemplateVariables(
        bool $sharedSpacesEnabled,
        ?int $memberCount,
        bool $oneLoginEnabled,
        ?string $sharedSpaceName,
    ): void {
        if ($sharedSpacesEnabled) {
            $this->sharedSpaceService->expects($this->once())
                ->method('getMemberCount')
                ->willReturn($memberCount);
        }

        $this->sharedSpaceService->expects($sharedSpaceName === null ? $this->never() : $this->once())
            ->method('getMember')
            ->with('1')
            ->willReturn(new SharedSpaceMember(['sharedSpaceName' => $sharedSpaceName]));

        $handler = new DeleteAccountHandler(
            $this->renderer,
            $this->authenticationService,
            $this->sharedSpaceService,
            $this->userService,
            $sharedSpacesEnabled,
            $oneLoginEnabled,
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
                    'memberCount' => $memberCount,
                    'oneLoginEnabled' => $oneLoginEnabled,
                    'sharedSpaceName' => $sharedSpaceName,
                ]
            )
            ->willReturn('<html>delete account</html>');

        $identity = new User('1', '', 1, new DateTime(), sharedSpaceId: '1');
        $response = $handler->handle($this->createRequest()->withAttribute(RequestAttribute::IDENTITY, $identity));

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
