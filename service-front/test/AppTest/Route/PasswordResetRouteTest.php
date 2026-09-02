<?php

declare(strict_types=1);

namespace AppTest\Route;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PasswordResetRouteTest extends TestCase
{
    private const string RESET_PATH = 'http://localhost/forgot-password/reset/';
    private const string ROUTE_NAME = 'forgot-password/callback';

    private FastRouteRouter $router;

    protected function setUp(): void
    {
        $this->router = new FastRouteRouter();

        $container = $this->createMock(ContainerInterface::class);
        $factory   = new MiddlewareFactory(new MiddlewareContainer($container));

        $app = new Application(
            $factory,
            new MiddlewarePipe(),
            new RouteCollector($this->router),
            $this->createMock(RequestHandlerRunnerInterface::class),
        );

        (require __DIR__ . '/../../../config/routes.php')($app, $factory, $container);
    }

    private function match(string $method, string $path): RouteResult
    {
        return $this->router->match(
            (new ServerRequest())->withMethod($method)->withUri(new Uri($path))
        );
    }

    #[DataProvider('resetTokens')]
    public function testResetLinkReachesTheResetHandler(string $method, string $token): void
    {
        $result = $this->match($method, self::RESET_PATH . $token);

        $this->assertTrue($result->isSuccess(), "Expected {$method} with token {$token} to match");
        $this->assertSame(self::ROUTE_NAME, $result->getMatchedRouteName());
    }

    /** @return array<string, array{string, string}> */
    public static function resetTokens(): array
    {
        $tokens = [
            'well formed'         => 'aaaaaaaaaaaaaaaaaaaa',
            'mixed case'          => 'aB3dEf7hIj0lMn5pQr9t',
            'trailing full stop'  => 'aaaaaaaaaaaaaaaaaaaa.',
            'trailing bracket'    => 'aaaaaaaaaaaaaaaaaaaa%3E',
            'trailing comma'      => 'aaaaaaaaaaaaaaaaaaaa,',
            'wrapped in brackets' => '%3Caaaaaaaaaaaaaaaaaaaa%3E',
            'encoded whitespace'  => 'aaaaaaaaaaaaaaaaaaaa%20',
            'trailing slash'      => 'aaaaaaaaaaaaaaaaaaaa/',
            'extra path segment'  => 'aaaaaaaaaaaaaaaaaaaa/extra',
        ];

        $cases = [];
        foreach ($tokens as $name => $token) {
            $cases["GET {$name}"]  = ['GET', $token];
            $cases["POST {$name}"] = ['POST', $token];
        }

        return $cases;
    }

    #[DataProvider('truncatedResetLinks')]
    public function testTruncatedResetLinkStillReachesTheResetHandler(string $path, ?string $expectedToken): void
    {
        $result = $this->match('GET', 'http://localhost' . $path);

        $this->assertTrue($result->isSuccess(), "Expected {$path} to match");
        $this->assertSame(self::ROUTE_NAME, $result->getMatchedRouteName());
        $this->assertSame($expectedToken, $result->getMatchedParams()['token'] ?? null);
    }

    /** @return array<string, array{string, ?string}> */
    public static function truncatedResetLinks(): array
    {
        return [
            'token stripped, slash kept' => ['/forgot-password/reset/', ''],
            'token and slash stripped'   => ['/forgot-password/reset', null],
        ];
    }

    public function testTokenContainingNewlinesDoesNotMatch(): void
    {
        $result = $this->match('GET', self::RESET_PATH . 'aaaaaaaaaaaaaaaaaaaa%0d%0aSet-Cookie:evil=1');

        $this->assertFalse($result->isSuccess(), 'Expected a token containing CRLF NOT to match');
    }

    public function testTokenIsPassedToTheHandler(): void
    {
        $result = $this->match('GET', self::RESET_PATH . 'aaaaaaaaaaaaaaaaaaaa.');

        $this->assertSame('aaaaaaaaaaaaaaaaaaaa.', $result->getMatchedParams()['token'] ?? null);
    }

    public function testGeneratingTheEmailedLinkRejectsATokenContainingCarriageReturns(): void
    {
        $this->assertSame(
            '/forgot-password/reset/abc123',
            $this->router->generateUri(self::ROUTE_NAME, ['token' => 'abc123']),
        );

        $this->expectException(RuntimeException::class);

        $this->router->generateUri(self::ROUTE_NAME, ['token' => "abc123\r\nBcc: evil@example.com"]);
    }

    public function testResetRouteIsReachableWhenSignedOut(): void
    {
        $route = $this->match('GET', self::RESET_PATH . 'aaaaaaaaaaaaaaaaaaaa')->getMatchedRoute();

        $this->assertNotFalse($route);
        $this->assertTrue(
            ($route->getOptions()['unauthenticated_route'] ?? false) === true,
            'Expected the password reset route to be flagged as an unauthenticated route',
        );
    }
}
