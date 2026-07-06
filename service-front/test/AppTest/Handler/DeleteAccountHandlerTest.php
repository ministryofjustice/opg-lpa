<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Authentication\AuthenticationService;
use App\Handler\DeleteAccountHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AuthenticationService&MockObject $authenticationService;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->authenticationService = $this->createMock(AuthenticationService::class);
    }

    public function testRendersDeleteAccountPageWithCommonTemplateVariables(): void
    {
        $handler = new DeleteAccountHandler($this->renderer, $this->authenticationService);

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
                ]
            )
            ->willReturn('<html>delete account</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
