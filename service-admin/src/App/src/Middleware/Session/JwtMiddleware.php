<?php

/**
Copyright (c) 2015-2022 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

Adapted for opg-lpa by Elliot Smith, Ministry of Justice (work-around to quash
critical security alert for the slim-jwt-auth package)

See https://github.com/tuupola/slim-jwt-auth/issues/217 for the bug this is
working around
*/

declare(strict_types=1);

namespace App\Middleware\Session;

/**
 * @see       https://github.com/tuupola/slim-jwt-auth
 * @see       https://appelsiini.net/projects/slim-jwt-auth
 * @license   https://www.opensource.org/licenses/mit-license.php
 */

use Closure;
use DomainException;
use InvalidArgumentException;
use Exception;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use App\Logging\LoggerTrait;
use Laminas\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use GuzzleHttp\Psr7\HttpFactory;

final class JwtMiddleware implements MiddlewareInterface
{
    use LoggerTrait;

    /**
     * Stores all the options passed to the middleware.
     * @var mixed[]
     */
    private $options = [
        "algorithm" => "HS256",
        "header" => "Authorization",
        "regexp" => "/Bearer\s+(.*)$/i",
        "cookie" => "token",
        "attribute" => "token",
        "before" => null,
        "after" => null,
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = lcfirst(ucwords($key));
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                /* Try to use setter */
                /** @phpstan-ignore-next-line */
                call_user_func([$this, $method], $value);
            } else {
                /**
                * Or fallback to setting option directly
                * @psalm-suppress InvalidPropertyAssignmentValue
                */
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Process a request in PSR-15 style and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $scheme = $request->getUri()->getScheme();

        /* Only HTTPS is allowed */
        if ("https" !== $scheme) {
            $message = sprintf(
                "Insecure use of middleware over %s denied by configuration.",
                $scheme
            );
            throw new RuntimeException($message);
        }

        /* If token cannot be found or decoded return with 401 Unauthorized. */
        try {
            $token = $this->fetchToken($request);
            $decoded = $this->decodeToken($token);
        } catch (RuntimeException | DomainException $exception) {
            return (new HttpFactory())->createResponse(401);
        }

        $params = [
            "decoded" => $decoded,
            "token" => $token,
        ];

        /* Add decoded token to request as attribute when requested. */
        if ($this->options["attribute"]) {
            $request = $request->withAttribute($this->options["attribute"], $decoded);
        }

        /* Modify $request before calling next middleware. */
        if (is_callable($this->options["before"])) {
            $beforeRequest = $this->options["before"]($request, $params);
            if ($beforeRequest instanceof ServerRequestInterface) {
                $request = $beforeRequest;
            }
        }

        /* Everything ok, call next middleware. */
        $response = $handler->handle($request);

        /* Modify $response before returning. */
        if (is_callable($this->options["after"])) {
            $afterResponse = $this->options["after"]($response, $params);
            if ($afterResponse instanceof ResponseInterface) {
                return $afterResponse;
            }
        }

        return $response;
    }

    /**
     * Fetch the access token.
     */
    private function fetchToken(ServerRequestInterface $request): string
    {
        /* Check for token in header. */
        $header = $request->getHeaderLine($this->options["header"]);

        if (false === empty($header)) {
            if (preg_match($this->options["regexp"], $header, $matches)) {
                $this->getLogger()->debug('Using token from request header');
                return $matches[1];
            }
        }

        /* Token not found in header try a cookie. */
        $cookieParams = $request->getCookieParams();

        if (isset($cookieParams[$this->options["cookie"]])) {
            $this->getLogger()->debug('Using token from cookie');
            if (preg_match($this->options["regexp"], $cookieParams[$this->options["cookie"]], $matches)) {
                return $matches[1];
            }
            return $cookieParams[$this->options["cookie"]];
        };

        /* If everything fails log and throw. */
        $this->getLogger()->warning('Token not found', [
            'error_code' => 'TOKEN_NOT_FOUND',
            'status' => Response::STATUS_CODE_500
        ]);
        throw new RuntimeException("Token not found.");
    }

    /**
     * Decode the token.
     *
     * @return mixed[]
     */
    private function decodeToken(#[\SensitiveParameter] string $token): array
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->options["secret"], $this->options["algorithm"]),
            );
            return (array) $decoded;
        } catch (Exception $exception) {
            $this->getLogger()->warning($exception->getMessage(), [$token]);
            $this->getLogger()->warning('Failed to decode JWT token', [
                'error_code' => 'JWT_DECODE_FAILED',
                'status' => $exception->getStatusCode(),
                'exception' => $exception,
            ]);
            throw $exception;
        }
    }

    /**
     * Set the cookie name where to search the token from.
     * @psalm-suppress UnusedMethod
     */
    private function cookie(#[\SensitiveParameter] string $cookie): void
    {
        $this->options["cookie"] = $cookie;
    }

    /**
     * Set the secret key.
     *
     * @param string|string[] $secret
     * @psalm-suppress UnusedMethod
     */
    private function secret(#[\SensitiveParameter] $secret): void
    {
        if (false === is_array($secret) && false === is_string($secret) && ! $secret instanceof \ArrayAccess) {
            throw new InvalidArgumentException(
                'Secret must be either a string or an array of "kid" => "secret" pairs'
            );
        }
        $this->options["secret"] = $secret;
    }

    /**
     * Set the attribute name used to attach decoded token to request.
     * @psalm-suppress UnusedMethod
     */
    private function attribute(string $attribute): void
    {
        $this->options["attribute"] = $attribute;
    }

    /**
     * Set the header where token is searched from.
     * @psalm-suppress UnusedMethod
     */
    private function header(string $header): void
    {
        $this->options["header"] = $header;
    }

    /**
     * Set the regexp used to extract token from header or environment.
     * @psalm-suppress UnusedMethod
     */
    private function regexp(string $regexp): void
    {
        $this->options["regexp"] = $regexp;
    }

    /**
     * Set the allowed algorithms
     *
     * @param string|string[] $algorithm
     * @psalm-suppress UnusedMethod
     */
    private function algorithm($algorithm): void
    {
        $this->options["algorithm"] = (array) $algorithm;
    }

    /**
     * Set the before handler.
     * @psalm-suppress UnusedMethod
     */

    private function before(callable $before): void
    {
        if ($before instanceof Closure) {
            $this->options["before"] = $before->bindTo($this);
        } else {
            $this->options["before"] = $before;
        }
    }

    /**
     * Set the after handler.
     * @psalm-suppress UnusedMethod
     */
    private function after(callable $after): void
    {
        if ($after instanceof Closure) {
            $this->options["after"] = $after->bindTo($this);
        } else {
            $this->options["after"] = $after;
        }
    }
}
