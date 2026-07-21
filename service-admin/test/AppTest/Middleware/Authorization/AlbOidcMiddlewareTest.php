<?php

declare(strict_types=1);

namespace AppTest\Middleware\Authorization;

use App\Middleware\Authorization\AlbOidcMiddleware;
use App\RequestAttributes;
use App\Service\Alb\PublicKeyClient;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AlbOidcMiddlewareTest extends TestCase
{
    private const string EXPECTED_ALB_ARN
        = 'arn:aws:elasticloadbalancing:eu-west-1:123456789012:loadbalancer/app/dev-admin/1234567890123456';

    private const string EXPECTED_CLIENT_ID = 'client-id';

    private PublicKeyClient|MockObject $albPublicKeyClient;
    private UrlHelper|MockObject $urlHelper;
    private RequestHandlerInterface|MockObject $handler;
    private AlbOidcMiddleware $middleware;

    protected function setUp(): void
    {
        $this->albPublicKeyClient = $this->createMock(PublicKeyClient::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new AlbOidcMiddleware(
            $this->albPublicKeyClient,
            self::EXPECTED_ALB_ARN,
            self::EXPECTED_CLIENT_ID,
            $this->urlHelper,
        );
    }

    public function testPassesEmptyClaimsWhenNoAlbHeader(): void
    {
        $request = new ServerRequest();

        $this->albPublicKeyClient->expects($this->never())
            ->method('fetchPublicKey');

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

    public function testRedirectsToSignInWhenTokenIsMalformed(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', 'not-a-jwt');

        $this->albPublicKeyClient->expects($this->never())
            ->method('fetchPublicKey');

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

    /**
     * Real (and mock, in local dev) ALB-signed tokens are ES256, signed with an ALB-specific
     * key (looked up by "kid"), and carry "signer"/"client" fields in the JWT header rather
     * than standard OIDC claims — the payload only contains plain user-info claims
     * (sub, email, ...), with no token_use/iss/aud.
     */
    public function testAcceptsValidAlbSignedToken(): void
    {
        [$privateKey, $publicKeyPem] = $this->generateEcKeyPair();

        $token = JWT::encode(
            ['sub' => 'user-123', 'email' => 'user@example.com'],
            $privateKey,
            'ES256',
            'test-kid',
            ['signer' => self::EXPECTED_ALB_ARN, 'client' => self::EXPECTED_CLIENT_ID],
        );

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', $token);

        $this->albPublicKeyClient->expects($this->once())
            ->method('fetchPublicKey')
            ->with('test-kid')
            ->willReturn($publicKeyPem);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($handledRequest) {
                $claims = $handledRequest->getAttribute(RequestAttributes::OIDC_CLAIMS);
                self::assertSame('user-123', $claims['sub']);
                self::assertSame('user@example.com', $claims['email']);
                return true;
            }))
            ->willReturn((new HttpFactory())->createResponse(200));

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRedirectsToSignInWhenAlbSignerArnDoesNotMatch(): void
    {
        [$privateKey] = $this->generateEcKeyPair();

        $token = JWT::encode(
            ['sub' => 'user-123', 'email' => 'user@example.com'],
            $privateKey,
            'ES256',
            'test-kid',
            [
                'signer' => 'arn:aws:elasticloadbalancing:eu-west-1:999999999999:loadbalancer/app/spoofed/0000000000000000',
                'client' => self::EXPECTED_CLIENT_ID,
            ],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', $token);

        $this->albPublicKeyClient->expects($this->never())
            ->method('fetchPublicKey');

        $logger->expects($this->once())
            ->method('warning');

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('sign.in')
            ->willReturn('/sign-in');

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/sign-in', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToSignInWhenAlbClientDoesNotMatch(): void
    {
        [$privateKey] = $this->generateEcKeyPair();

        $token = JWT::encode(
            ['sub' => 'user-123', 'email' => 'user@example.com'],
            $privateKey,
            'ES256',
            'test-kid',
            ['signer' => self::EXPECTED_ALB_ARN, 'client' => 'wrong-client-id'],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', $token);

        $this->albPublicKeyClient->expects($this->never())
            ->method('fetchPublicKey');

        $logger->expects($this->once())
            ->method('warning');

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('sign.in')
            ->willReturn('/sign-in');

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/sign-in', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToSignInWhenSignatureIsInvalid(): void
    {
        [, $publicKeyPem] = $this->generateEcKeyPair();
        [$otherPrivateKey] = $this->generateEcKeyPair();

        // Signed with a different key than the one PublicKeyClient will return.
        $token = JWT::encode(
            ['sub' => 'user-123', 'email' => 'user@example.com'],
            $otherPrivateKey,
            'ES256',
            'test-kid',
            ['signer' => self::EXPECTED_ALB_ARN, 'client' => self::EXPECTED_CLIENT_ID],
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);

        $request = (new ServerRequest())->withHeader('X-Amzn-Oidc-Data', $token);

        $this->albPublicKeyClient->expects($this->once())
            ->method('fetchPublicKey')
            ->with('test-kid')
            ->willReturn($publicKeyPem);

        $logger->expects($this->once())
            ->method('warning');

        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('sign.in')
            ->willReturn('/sign-in');

        $response = $this->middleware->process($request, $this->handler);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/sign-in', $response->getHeaderLine('Location'));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function generateEcKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        self::assertNotFalse($resource);

        openssl_pkey_export($resource, $privateKeyPem);
        $details = openssl_pkey_get_details($resource);

        return [$privateKeyPem, $details['key']];
    }
}
