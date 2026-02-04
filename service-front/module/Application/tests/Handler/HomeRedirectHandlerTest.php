<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\HomeRedirectHandler;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class HomeRedirectHandlerTest extends TestCase
{
    public function testRedirectsToConfiguredUrl(): void
    {
        $config = [
            'redirects' => ['index' => 'https://example.com/home'],
        ];

        $handler = new HomeRedirectHandler($config);

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/home', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToRootWhenConfigMissing(): void
    {
        $config = [];

        $handler = new HomeRedirectHandler($config);

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testResponseIs302Redirect(): void
    {
        $config = [
            'redirects' => ['index' => '/somewhere'],
        ];

        $handler = new HomeRedirectHandler($config);

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
    }
}
