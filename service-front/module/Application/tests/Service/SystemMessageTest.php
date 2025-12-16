<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\Service\SystemMessage;
use PHPUnit\Framework\TestCase;

final class SystemMessageTest extends TestCase
{
    public function testFetchSanitisedReturnsNullWhenCacheReturnsNull(): void
    {
        $cache = $this->createMock(DynamoDbKeyValueStore::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->with('system-message')
            ->willReturn(null);

        $service = new SystemMessage($cache);

        $this->assertNull($service->fetchSanitised());
    }

    public function testFetchSanitisedReturnsNullWhenCacheReturnsEmptyString(): void
    {
        $cache = $this->createMock(DynamoDbKeyValueStore::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->with('system-message')
            ->willReturn('   ');

        $service = new SystemMessage($cache);

        $this->assertNull($service->fetchSanitised());
    }

    public function testFetchSanitisedTrimsAndEscapesMessage(): void
    {
        $cache = $this->createMock(DynamoDbKeyValueStore::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->with('system-message')
            ->willReturn('  <script>alert("hello");</script>test message &   ');

        $service = new SystemMessage($cache);

        $this->assertSame(
            '&lt;script&gt;alert(&quot;hello&quot;);&lt;/script&gt;test message &amp;',
            $service->fetchSanitised()
        );
    }

    public function testFetchSanitisedReturnsNullForNonString(): void
    {
        $cache = $this->createMock(DynamoDbKeyValueStore::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->with('system-message')
            ->willReturn(['not', 'a', 'string']);

        $service = new SystemMessage($cache);

        $this->assertNull($service->fetchSanitised());
    }
}
