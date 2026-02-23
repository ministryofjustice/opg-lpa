<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\HomeHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HomeHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersHomePageWithLpaFeeAndDockerTag(): void
    {
        $config = [
            'version' => ['tag' => 'v1.2.3'],
        ];

        $handler = new HomeHandler($this->renderer, $config);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/home/index.twig',
                $this->callback(function ($params) {
                    return isset($params['lpaFee'])
                        && isset($params['dockerTag'])
                        && $params['dockerTag'] === 'v1.2.3';
                })
            )
            ->willReturn('<html>home</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandlesMissingVersionConfig(): void
    {
        $config = [];

        $handler = new HomeHandler($this->renderer, $config);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/home/index.twig',
                $this->callback(function ($params) {
                    return $params['dockerTag'] === '';
                })
            )
            ->willReturn('<html>home</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
