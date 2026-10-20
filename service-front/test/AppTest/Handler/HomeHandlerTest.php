<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomeHandler;
use DateTime;
use DateTimeZone;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HomeHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    private function at(string $date): DateTime
    {
        return new DateTime($date, new DateTimeZone('UTC'));
    }

    public static function bannerDates(): array
    {
        return [
            'before start date' => ['2026-11-10 23:59:59', false],
            'start date' => ['2026-11-11 00:00:00', true],
            'inside window' => ['2026-11-25 23:59:59', true],
            'after end date' => ['2026-11-26 00:00:00', false],
        ];
    }

    #[DataProvider('bannerDates')]
    public function testBannerShownOnlyWithinWindow(string $date, bool $expected): void
    {
        $handler = new HomeHandler($this->renderer, [], $this->at($date));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(fn($params) => $params['showOneLoginChangesBanner'] === $expected)
            )
            ->willReturn('<html>home</html>');

        $handler->handle(new ServerRequest());
    }

    public function testRendersHomePageWithLpaFeeAndDockerTag(): void
    {
        $config = [
            'version' => ['tag' => 'v1.2.3'],
        ];

        $handler = new HomeHandler($this->renderer, $config, $this->at('2026-11-15'));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/home/index.twig',
                $this->callback(function ($params) {
                    return isset($params['lpaFee'])
                        && isset($params['dockerTag'])
                        && isset($params['showOneLoginChangesBanner'])
                        && $params['showOneLoginChangesBanner'] === true
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

        $handler = new HomeHandler($this->renderer, $config, $this->at('2026-01-01'));

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
