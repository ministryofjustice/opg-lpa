<?php

declare(strict_types=1);

namespace AppTest\Middleware\Authorization;

use App\Middleware\Authorization\AlbOidcMiddleware;
use App\RequestAttributes;
use App\Service\Cognito\Client as CognitoClient;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AlbOidcMiddlewareTest extends TestCase
{
    private CognitoClient|MockObject $cognitoClient;
    private UrlHelper|MockObject $urlHelper;
    private RequestHandlerInterface|MockObject $handler;
    private AlbOidcMiddleware $middleware;

    protected function setUp(): void
    {
        $this->cognitoClient = $this->createMock(CognitoClient::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new AlbOidcMiddleware(
            $this->cognitoClient,
            'https://issuer.example',
            'client-id',
            $this->urlHelper,
        );
    }

    public function testPassesEmptyClaimsWhenNoAlbHeader(): void
    {
        $request = new ServerRequest();

        $this->cognitoClient->expects($this->never())
            ->method('fetchJwks');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) {
                self::assertSame([], $handledRequest->getAttribute(RequestAttributes::OIDC_CLAIMS));
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRedirectsToSignInWhenTokenDecodeFailsWithEmptyJwks(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', 'not-a-jwt');

        $this->cognitoClient->expects($this->once())
            ->method('fetchJwks')
            ->with(false)
            ->willReturn(['keys' => []]);

        $logger->expects($this->once())
            ->method('warning');

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('sign.in')
            ->willReturn('/sign-in');

        $this->handler->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/sign-in', $response->getHeaderLine('Location'));
    }
}
