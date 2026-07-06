<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use App\RequestAttributes;
use Exception;
use Fig\Http\Message\RequestMethodInterface;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use GuzzleHttp\Psr7\Request;
use MakeShared\Logging\LoggerTrait;
use Psr\Http\Client\ClientInterface;
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
        private readonly ClientInterface $httpClient,
        private readonly string $jwksUri,
        private readonly string $issuer,
        private readonly string $clientId,
    ) {
    }

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeaderLine(self::AWS_ALB_OIDC_COGNITO_HEADER);

        if (empty($token)) {
            throw new Exception('Missing OIDC token in request header');
        }

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    RequestMethodInterface::METHOD_GET,
                    $this->jwksUri . '/.well-known/jwks',
                    ['Content-Type' => 'application/json']
                )
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error communicating with mock Cognito service', 0, $e);
        }

        $claims = [];

        try {
            $keys = JWK::parseKeySet(
                json_decode($response->getBody()->getContents(), true)
            );
            $claims = (array) JWT::decode($token, $keys);
        } catch (ExpiredException $e) {
            $this->getLogger()->warning('ALB OIDC token expired', ['exception' => $e]);
        } catch (SignatureInvalidException $e) {
            $this->getLogger()->warning('ALB OIDC token signature invalid', ['exception' => $e]);
        } catch (\Exception $e) {
            $this->getLogger()->warning('ALB OIDC token decode failed', ['exception' => $e]);
        }

        if (!$this->validateClaims($claims)) {
            $this->getLogger()->warning('ALB OIDC token claims invalid', ['claims' => $claims]);
        }

        return $handler->handle(
            $request->withAttribute(RequestAttributes::OIDC_CLAIMS, $claims)
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
