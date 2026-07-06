<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomeRedirectHandler;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class HomeRedirectHandlerTest extends TestCase
{
    public function testRedirectsToConfiguredIndex(): void
    {
        $handler = new HomeRedirectHandler([
            'redirects' => [
                'index' => '/home',
            ],
        ]);

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/home', $response->getHeaderLine('Location'));
    }

    public function testDefaultsRedirectToRootWhenConfigMissing(): void
    {
        $handler = new HomeRedirectHandler([]);

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }
}
