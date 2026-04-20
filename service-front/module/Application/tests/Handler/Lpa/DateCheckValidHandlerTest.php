<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\DateCheckValidHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateCheckValidHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private DateCheckValidHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->handler = new DateCheckValidHandler($this->renderer);
    }

    private function createRequest(array $queryParams = []): ServerRequest
    {
        return (new ServerRequest())
            ->withMethod('GET')
            ->withQueryParams($queryParams);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function returnRouteProvider(): array
    {
        return [
            'with return-route query param' => [
                ['return-route' => 'lpa/complete'],
                'lpa/complete',
            ],
            'without return-route defaults to dashboard' => [
                [],
                'user/dashboard',
            ],
        ];
    }

    #[DataProvider('returnRouteProvider')]
    public function testHandleRendersValidTemplateWithReturnRoute(
        array $queryParams,
        string $expectedReturnRoute,
    ): void {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/date-check/valid.twig',
                $this->callback(function (array $vars) use ($expectedReturnRoute) {
                    return $vars['returnRoute'] === $expectedReturnRoute;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($queryParams));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testHandleReturnsHtmlResponse(): void
    {
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame('rendered-html', (string) $response->getBody());
    }
}
