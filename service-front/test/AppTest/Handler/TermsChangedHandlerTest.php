<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\TermsChangedHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TermsChangedHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersTermsChangedPageWithCommonTemplateVariables(): void
    {
        $handler = new TermsChangedHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/dashboard/terms.twig',
                [
                    'signedInUser' => null,
                    'secondsUntilSessionExpires' => null,
                    'lpa' => null,
                    'currentRouteName' => null,
                    'csrfToken' => null,
                ]
            )
            ->willReturn('<html>terms changed</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
