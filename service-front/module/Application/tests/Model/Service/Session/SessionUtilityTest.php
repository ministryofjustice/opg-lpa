<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionUtility;
use Laminas\Session\Container as LaminasContainer;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

class SessionUtilityTest extends TestCase
{
    private const CONTAINER_NAME = 'test_session_utility';

    protected function setUp(): void
    {
        $container = new LaminasContainer(self::CONTAINER_NAME);
        foreach ($container as $key => $value) {
            unset($container->$key);
        }

        parent::setUp();
    }

    public function testSetGetUnsetMvc(): void
    {
        $util = new SessionUtility();

        $util->setInMvc(self::CONTAINER_NAME, 'foo', 'bar');

        $container = new LaminasContainer(self::CONTAINER_NAME);
        self::assertSame('bar', $container->foo);

        $value = $util->getFromMvc(self::CONTAINER_NAME, 'foo');
        self::assertSame('bar', $value);

        $default = $util->getFromMvc(self::CONTAINER_NAME, 'does_not_exist', 'fallback');
        self::assertSame('fallback', $default);

        $util->unsetInMvc(self::CONTAINER_NAME, 'foo');
        self::assertFalse(isset($container->foo));
    }

    public function testSetInMezzio(): void
    {
        $util = new SessionUtility();

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::once())
            ->method('set')
            ->with('foo', 'bar');

        $util->setInMezzio($session, 'foo', 'bar');
    }

    public function testGetFromMezzio(): void
    {
        $util = new SessionUtility();

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::once())
            ->method('get')
            ->with('foo', 'fallback')
            ->willReturn('value');

        $value = $util->getFromMezzio($session, 'foo', 'fallback');

        self::assertSame('value', $value);
    }

    public function testHasInMezzioUsesHasMethod(): void
    {
        $util = new SessionUtility();

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        self::assertTrue($util->hasInMezzio($session, 'foo'));
    }

    public function testUnsetInMezzioUsesUnsetMethod(): void
    {
        $util = new SessionUtility();

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::once())
            ->method('unset')
            ->with('foo');

        $util->unsetInMezzio($session, 'foo');
    }
}
