<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\RequestAttributes;
use App\Service\Cognito\Client as CognitoClient;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
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

class AlbOidcMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public const string AWS_ALB_OIDC_COGNITO_HEADER = 'X-Amzn-Oidc-Data';

    public function __construct(
        private readonly CognitoClient $cognitoClient,
        private readonly string $issuer,
        private readonly string $clientId,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine(self::AWS_ALB_OIDC_COGNITO_HEADER);

        // In production the ALB always injects this header before PHP sees the request.
        // If it's missing (e.g. local dev with signed-out state), treat as unauthenticated
        // by passing empty claims — AuthorizationMiddleware will redirect to sign.in.
        if (empty($token)) {
            return $handler->handle(
                $request->withAttribute(RequestAttributes::OIDC_CLAIMS, [])
            );
        }

        $claims = [];

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

        if (!$this->validateClaims($claims)) {
            $this->getLogger()->warning('ALB OIDC token claims invalid — redirecting to sign-in', ['claims' => $claims]);
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
     * Decodes the JWT using cached JWKS. On an unknown-key failure, invalidates
     * the cache and retries once to handle key rotation without a TTL-length outage.
     *
     * @return array<string, mixed>
     */
    private function decodeToken(string $token): array
    {
        try {
            $keys = JWK::parseKeySet($this->cognitoClient->fetchJwks());
            return (array) JWT::decode($token, $keys);
        } catch (\UnexpectedValueException $e) {
            // "Kid" not found in JWKS — likely a key rotation. Refresh and retry once.
            if (str_contains($e->getMessage(), 'kid')) {
                $this->getLogger()->info('JWKS kid not found, refreshing cache after possible key rotation');
                $keys = JWK::parseKeySet($this->cognitoClient->fetchJwks(forceRefresh: true));
                return (array) JWT::decode($token, $keys);
            }
            throw $e;
        }
    }

    private function validateClaims(array $claims): bool
    {
        if (($claims['token_use'] ?? '') !== 'id') {
            return false;
        }

        if (($claims['iss'] ?? '') !== $this->issuer) {
            return false;
        }

        $aud = (array) ($claims['aud'] ?? []);
        if (!in_array($this->clientId, $aud, strict: true)) {
            return false;
        }

        return true;
    }
}
