<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\RequestAttributes;
use App\Service\Alb\PublicKeyClient;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Verifies the X-Amzn-Oidc-Data header an Application Load Balancer injects once it has
 * completed OIDC authentication with Cognito.
 *
 * Important: the ALB does NOT forward the raw Cognito ID token. It repackages the
 * authenticated user's claims (sub, email, ...) into its own JWT, signed with ES256 using
 * a key unique to the ALB — not Cognito's JWKS. That key is looked up by "kid" from
 * https://public-keys.auth.elb.<region>.amazonaws.com/<kid>, and as a security best
 * practice the "signer" and "client" fields in the JWT header are checked against the
 * expected ALB ARN and Cognito app client ID.
 *
 * In local development, the mock ALB/Cognito server (see mock-cognito/) produces tokens
 * in this exact same shape, so this middleware behaves identically in both environments —
 * see PublicKeyClient's $baseUrl, which just points somewhere else locally.
 *
 * @see https://docs.aws.amazon.com/elasticloadbalancing/latest/application/listener-authenticate-users.html#user-claims-encoding
 */
class AlbOidcMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public const string AWS_ALB_OIDC_COGNITO_HEADER = 'X-Amzn-Oidc-Data';

    private const string ALB_SIGNING_ALGORITHM = 'ES256';

    public function __construct(
        private readonly PublicKeyClient $albPublicKeyClient,
        private readonly string $expectedAlbSigner,
        private readonly string $expectedClientId,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine(self::AWS_ALB_OIDC_COGNITO_HEADER);

        // The ALB always injects this header before PHP sees the request. If it's missing
        // (e.g. local dev with signed-out state), treat as unauthenticated by passing empty
        // claims — AuthorizationMiddleware will redirect to sign.in.
        if (empty($token)) {
            return $handler->handle(
                $request->withAttribute(RequestAttributes::OIDC_CLAIMS, [])
            );
        }

        try {
            $claims = $this->decodeToken($token);
        } catch (ExpiredException $e) {
            $this->getLogger()->warning('ALB OIDC token expired — redirecting to sign-in', ['exception' => $e]);
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        } catch (SignatureInvalidException $e) {
            $this->getLogger()->warning('ALB OIDC token signature invalid — redirecting to sign-in', ['exception' => $e]);
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        } catch (\Exception $e) {
            $this->getLogger()->warning('ALB OIDC token decode failed — redirecting to sign-in', ['exception' => $e]);
            return new RedirectResponse($this->urlHelper->generate('sign.in'));
        }

        return $handler->handle(
            $request->withAttribute(RequestAttributes::OIDC_CLAIMS, $claims)
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeToken(#[\SensitiveParameter] string $token): array
    {
        $header = $this->decodeJwtHeader($token);

        if (($header['signer'] ?? null) !== $this->expectedAlbSigner) {
            throw new SignatureInvalidException(
                sprintf('Unexpected ALB signer ARN "%s"', $header['signer'] ?? '(missing)')
            );
        }

        if (($header['client'] ?? null) !== $this->expectedClientId) {
            throw new SignatureInvalidException(
                sprintf('Unexpected ALB client "%s"', $header['client'] ?? '(missing)')
            );
        }

        if (empty($header['kid'])) {
            throw new UnexpectedValueException('ALB OIDC token header missing "kid"');
        }

        $publicKey = $this->albPublicKeyClient->fetchPublicKey($header['kid']);

        return (array) JWT::decode($token, new Key($publicKey, self::ALB_SIGNING_ALGORITHM));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtHeader(#[\SensitiveParameter] string $token): array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new UnexpectedValueException('Malformed JWT — expected 3 segments');
        }

        $decoded = json_decode(JWT::urlsafeB64Decode($segments[0]), true);
        if (!is_array($decoded)) {
            throw new UnexpectedValueException('Malformed JWT header');
        }

        return $decoded;
    }
}
