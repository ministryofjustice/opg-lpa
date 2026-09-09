<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Authentication\AuthenticationService;
use App\Handler\DeleteAccountHandler;
use App\Service\SharedSpace\SharedSpaceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;
    private SharedSpaceService&MockObject $sharedSpaceService;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sharedSpaceService = $this->createMock(SharedSpaceService::class);
    }

    public function testRendersDeleteAccountPageWithCommonTemplateVariables(): void
    {
        $this->sharedSpaceService->expects($this->once())
            ->method('getMemberCount')
            ->willReturn(5);

        $handler = new DeleteAccountHandler($this->renderer, $this->authenticationService, $this->sharedSpaceService);

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
                    'csrfToken' => null,
                    'memberCount' => 5
                ]
            )
            ->willReturn('<html>delete account</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
