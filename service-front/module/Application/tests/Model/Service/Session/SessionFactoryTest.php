<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Application\Model\Service\Session\SessionFactory;
use Laminas\Session\SessionManager;
use Psr\Container\ContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\Http\Request;
use Laminas\Http\Uri;
use Laminas\Session\Exception\RuntimeException;

final class SessionFactoryTest extends MockeryTestCase
{
    /**
     * Because SessionFactory messes with ini_set, we have to run this test
     * in its own process.
     */
    #[RunInSeparateProcess]
    public function testSessionFactory(): void
    {
        $uri = Mockery::Mock(Uri::class);
        $uri
            ->shouldReceive('getHost')
            ->andReturn('foo');

        $request = Mockery::Mock(Request::class);
        $request
            ->shouldReceive('getUri')
            ->andReturn($uri);

        $container = Mockery::Mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->withArgs(['Config'])
            ->once()
            ->andReturn([
                'session' => [
                    'native_settings' => [
                        'name' => 'foo'
                    ]
                ]
            ]);

        $container
            ->shouldReceive('get')
            ->withArgs(['Request'])
            ->andReturn($request);

        $container
            ->shouldReceive('get')
            ->with('SaveHandler');

        $factory = new SessionFactory();
        $result = $factory($container, null, null);

        $this->assertInstanceOf(SessionManager::class, $result);
        $this->assertEquals(ini_get('session.name'), 'foo');
    }

    public function testSessionFactoryNoSessionConfig(): void
    {
        /** @var ContainerInterface|MockInterface $container */
        $container = Mockery::Mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->withArgs(['Config'])
            ->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session configuration setting not found');

        $factory = new SessionFactory();
        $factory($container, null, null);
    }
}
