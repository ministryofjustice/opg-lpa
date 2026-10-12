<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HealthCheckHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class HealthCheckHandlerTest extends TestCase
{
    public function testRendersPingTemplateWithStatus(): void
    {
        $response = (new HealthCheckHandler(['version' => ['tag' => 'x']]))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"ok":true,"tag":"x"}', (string) $response->getBody());
    }
}
