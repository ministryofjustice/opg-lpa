<?php

declare(strict_types=1);

namespace AppTest\Route;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests that the /login route regex constraint prevents invalid state values
 * (e.g. /login/login.jsp) from matching and returning 200.
 */
class LoginRouteTest extends TestCase
{
    private const string ROUTE_PATH = '/login[/{state:(?:timeout|internalSystemError)}]';
    private const array ALLOWED_METHODS = ['GET', 'POST'];

    private FastRouteRouter $router;

    protected function setUp(): void
    {
        $this->router = new FastRouteRouter();

        $stub = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        $this->router->addRoute(new Route(self::ROUTE_PATH, $stub, self::ALLOWED_METHODS, 'application.login'));
    }

    #[DataProvider('validLoginPaths')]
    public function testValidPathsMatch(string $method, string $path): void
    {
        $request = (new ServerRequest())->withMethod($method)->withUri(new Uri($path));
        $result = $this->router->match($request);

        $this->assertTrue($result->isSuccess(), "Expected {$method} {$path} to match the login route");
    }

    /** @return array<string, array{string, string}> */
    public static function validLoginPaths(): array
    {
        return [
            'GET /login'                      => ['GET',  'http://localhost/login'],
            'POST /login'                     => ['POST', 'http://localhost/login'],
            'GET /login/timeout'              => ['GET',  'http://localhost/login/timeout'],
            'POST /login/timeout'             => ['POST', 'http://localhost/login/timeout'],
            'GET /login/internalSystemError'  => ['GET',  'http://localhost/login/internalSystemError'],
            'POST /login/internalSystemError' => ['POST', 'http://localhost/login/internalSystemError'],
        ];
    }

    #[DataProvider('invalidStatePaths')]
    public function testInvalidStatePathsDoNotMatch(string $path): void
    {
        $request = (new ServerRequest())->withMethod('GET')->withUri(new Uri($path));
        $result = $this->router->match($request);

        $this->assertFalse($result->isSuccess(), "Expected GET {$path} NOT to match the login route");
    }

    /** @return array<string, array{string}> */
    public static function invalidStatePaths(): array
    {
        return [
            'login.jsp suffix'   => ['http://localhost/login/login.jsp'],
            'arbitrary string'   => ['http://localhost/login/foobar'],
            'numeric state'      => ['http://localhost/login/123'],
            'partial state name' => ['http://localhost/login/time'],
            'extra path segment' => ['http://localhost/login/timeout/extra'],
        ];
    }

    #[DataProvider('unsupportedMethods')]
    public function testUnsupportedMethodsDoNotSucceed(string $method): void
    {
        $request = (new ServerRequest())->withMethod($method)->withUri(new Uri('http://localhost/login'));
        $result = $this->router->match($request);

        $this->assertFalse($result->isSuccess(), "Expected {$method} /login NOT to succeed");
        $this->assertTrue($result->isMethodFailure(), "Expected {$method} /login to be a method failure");
    }

    /** @return array<string, array{string}> */
    public static function unsupportedMethods(): array
    {
        return [
            'DELETE' => ['DELETE'],
            'PUT'    => ['PUT'],
            'PATCH'  => ['PATCH'],
        ];
    }
}
