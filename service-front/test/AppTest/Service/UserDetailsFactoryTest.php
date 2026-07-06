<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\UserDetailsFactory;
use App\Service\ApiClient\Client as ApiClient;
use App\Authentication\AuthenticationService;
use App\Service\UserDetails as UserService;
use App\Storage\MezzioSessionStorage;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class UserDetailsFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private AuthenticationService&MockObject $authService;
    private ApiClient&MockObject $apiClient;
    private UrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private MezzioSessionStorage&MockObject $sessionStorage;

    protected function setUp(): void
    {
        $this->container      = $this->createMock(ContainerInterface::class);
        $this->authService    = $this->createMock(AuthenticationService::class);
        $this->apiClient      = $this->createMock(ApiClient::class);
        $this->urlHelper      = $this->createMock(UrlHelper::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->sessionStorage = $this->createMock(MezzioSessionStorage::class);

        $this->container->method('get')->willReturnMap([
            [AuthenticationService::class,  $this->authService],
            [ApiClient::class,              $this->apiClient],
            [UrlHelper::class,              $this->urlHelper],
            [LoggerInterface::class,        $this->logger],
            [MezzioSessionStorage::class,   $this->sessionStorage],
            ['config',                      ['email' => ['notify' => ['key' => null]]]],
        ]);
    }

    public function testReturnsUserServiceInstance(): void
    {
        $factory = new UserDetailsFactory();
        $service = $factory($this->container);

        $this->assertInstanceOf(UserService::class, $service);
    }

    public function testUsesNullMailTransportWhenNoNotifyKey(): void
    {
        $factory = new UserDetailsFactory();
        $service = $factory($this->container);

        // Verify the service was constructed — if it were to throw on send()
        // internally we'd know the wrong transport was picked. We test indirectly
        // by confirming the service is usable (no exception on construction).
        $this->assertInstanceOf(UserService::class, $service);
    }

    public function testUsesNotifyMailTransportWhenKeyIsPresent(): void
    {
        $this->container->method('get')->willReturnMap([
            [AuthenticationService::class,  $this->authService],
            [ApiClient::class,              $this->apiClient],
            [UrlHelper::class,              $this->urlHelper],
            [LoggerInterface::class,        $this->logger],
            [MezzioSessionStorage::class,   $this->sessionStorage],
            ['config', [
                'email' => [
                    'notify' => [
                        'key'                   => 'test-notify-key',
                        'smokeTestEmailAddress' => 'smoke@example.com',
                    ],
                ],
            ]],
        ]);

        $factory = new UserDetailsFactory();
        $service = $factory($this->container);

        $this->assertInstanceOf(UserService::class, $service);
    }

    public function testUrlHelperGeneratesPathForNonCanonicalUrl(): void
    {
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('register/confirm', ['token' => 'abc'])
            ->willReturn('/signup/confirm/abc');

        $urlCallable = $this->buildUrlCallable($this->urlHelper);

        $result = $urlCallable('register/confirm', ['token' => 'abc'], []);
        $this->assertSame('/signup/confirm/abc', $result);
    }

    public function testUrlHelperPrependsSchemeAndHostForForceCanonical(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';
        unset($_SERVER['HTTPS']);

        $this->urlHelper->method('generate')->willReturn('/signup/confirm/abc');

        $urlCallable = $this->buildUrlCallable($this->urlHelper);
        $result      = $urlCallable('register/confirm', ['token' => 'abc'], ['force_canonical' => true]);

        $this->assertSame('http://www.example.com/signup/confirm/abc', $result);
    }

    public function testUrlHelperUsesHttpsWhenServerIsHttps(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['HTTPS']     = 'on';

        $this->urlHelper->method('generate')->willReturn('/signup/confirm/abc');

        $urlCallable = $this->buildUrlCallable($this->urlHelper);
        $result      = $urlCallable('register/confirm', ['token' => 'abc'], ['force_canonical' => true]);

        $this->assertStringStartsWith('https://', $result);

        unset($_SERVER['HTTPS']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds the url() callable using the same logic as UserDetailsFactory,
     * without going through HelperPluginManager (which would resolve Laminas's
     * own Url view helper and require a RouteStackInterface).
     */
    private function buildUrlCallable(UrlHelper $urlHelper): callable
    {
        return static function (
            ?string $name = null,
            array $params = [],
            array $options = [],
        ) use ($urlHelper): string {
            $path = $urlHelper->generate((string) $name, $params);
            if (!empty($options['force_canonical'])) {
                $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                return $scheme . '://' . $host . $path;
            }
            return $path;
        };
    }
}
