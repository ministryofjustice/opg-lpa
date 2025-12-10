<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\CookiesHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Laminas\Http\Request as HttpRequest;
use Mezzio\Template\TemplateRendererInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CookiesHandlerTest extends MockeryTestCase
{
    public function testHandleRendersTwigTemplate(): void
    {
        $renderer = Mockery::mock(TemplateRendererInterface::class);
        $formElementManager = Mockery::mock(FormElementManager::class);
        $httpRequest = Mockery::mock(HttpRequest::class);
        $form = Mockery::mock(FormInterface::class);

        $formElementManager
            ->shouldReceive('get')
            ->once()
            ->with('Application\Form\General\CookieConsentForm')
            ->andReturn($form);

        $form
            ->shouldReceive('setAttribute')
            ->once()
            ->with('action', '/cookies');

        $httpRequest
            ->shouldReceive('getCookie')
            ->once()
            ->andReturn(false);

        $renderer
            ->shouldReceive('render')
            ->once()
            ->withArgs(function (string $template, array $context) use ($form): bool {
                return $template === 'application/general/cookies/index.twig'
                    && array_key_exists('form', $context)
                    && $context['form'] === $form;
            })
            ->andReturn('<html>cookies page</html>');

        $handler = new CookiesHandler(
            $renderer,
            $formElementManager,
            $httpRequest,
        );

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame('<html>cookies page</html>', (string) $response->getBody());
    }
}
