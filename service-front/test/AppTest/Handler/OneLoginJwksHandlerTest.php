<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginJwksHandler;
use App\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class OneLoginJwksHandlerTest extends TestCase
{
    public function testPublishesOurPublicKeysAsJson(): void
    {
        $jwks = ['keys' => [['kty' => 'RSA', 'kid' => 'kid-1', 'use' => 'sig', 'alg' => 'RS256', 'n' => 'n', 'e' => 'AQAB']]];

        $oneLoginService = $this->createMock(OneLoginService::class);
        $oneLoginService->expects($this->once())->method('jwks')->willReturn($jwks);

        $response = (new OneLoginJwksHandler($oneLoginService))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertFalse($response->hasHeader('Cache-Control'), 'must not be marked publicly cacheable');
        $this->assertSame($jwks, json_decode((string) $response->getBody(), true));
    }
}
