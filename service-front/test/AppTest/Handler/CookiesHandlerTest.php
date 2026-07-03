<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\CookiesHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\Element\Radio;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CookiesHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private FormInterface&MockObject $form;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with('App\Form\General\CookieConsentForm')
            ->willReturn($this->form);
    }

    public function testRendersCookieFormAndSetsAction(): void
    {
        $handler = new CookiesHandler($this->renderer, $this->formElementManager);

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/cookies');

        $this->form
            ->expects($this->never())
            ->method('get');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/cookies/index.twig', ['form' => $this->form])
            ->willReturn('<html>cookies</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSetsUsageCookiesRadioToYesFromCookiePolicy(): void
    {
        $handler = new CookiesHandler($this->renderer, $this->formElementManager);
        $radio = $this->createMock(Radio::class);

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/cookies');

        $this->form
            ->expects($this->once())
            ->method('get')
            ->with('usageCookies')
            ->willReturn($radio);

        $radio
            ->expects($this->once())
            ->method('setValue')
            ->with('yes');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/cookies/index.twig', ['form' => $this->form])
            ->willReturn('<html>cookies</html>');

        $request = new ServerRequest(
            cookieParams: [
                CookiesHandler::COOKIE_POLICY_NAME => '{"usage":true}',
            ]
        );

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSetsUsageCookiesRadioToNoFromCookiePolicy(): void
    {
        $handler = new CookiesHandler($this->renderer, $this->formElementManager);
        $radio = $this->createMock(Radio::class);

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/cookies');

        $this->form
            ->expects($this->once())
            ->method('get')
            ->with('usageCookies')
            ->willReturn($radio);

        $radio
            ->expects($this->once())
            ->method('setValue')
            ->with('no');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/cookies/index.twig', ['form' => $this->form])
            ->willReturn('<html>cookies</html>');

        $request = new ServerRequest(
            cookieParams: [
                CookiesHandler::COOKIE_POLICY_NAME => '{"usage":false}',
            ]
        );

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testIgnoresInvalidCookiePolicyPayload(): void
    {
        $handler = new CookiesHandler($this->renderer, $this->formElementManager);

        $this->form
            ->expects($this->once())
            ->method('setAttribute')
            ->with('action', '/cookies');

        $this->form
            ->expects($this->never())
            ->method('get');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/cookies/index.twig', ['form' => $this->form])
            ->willReturn('<html>cookies</html>');

        $request = new ServerRequest(
            cookieParams: [
                CookiesHandler::COOKIE_POLICY_NAME => 'not-json',
            ]
        );

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
