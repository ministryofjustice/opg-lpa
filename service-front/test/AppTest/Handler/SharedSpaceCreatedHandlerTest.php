<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SharedSpaceCreatedHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedSpaceCreatedHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private SharedSpaceCreatedHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->handler = new SharedSpaceCreatedHandler(
            $this->renderer,
        );
    }

    public function testGetRequestDisplaysPage(): void
    {
        $this->renderer->method('render')
            ->with('application/authenticated/shared-space/created.twig', [
                'signedInUser' => null,
                'secondsUntilSessionExpires' => null,
                'lpa' => null,
                'currentRouteName' => null,
                'csrfToken' => null,
                'sharedSpaceName' => 'Test Space',
            ])
            ->willReturn('<html>make shared space form</html>');

        $request = new ServerRequest()->withQueryParams(['space-name' => 'Test Space']);
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
