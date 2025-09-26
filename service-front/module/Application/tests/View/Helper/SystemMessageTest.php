<?php

declare(strict_types=1);

namespace ApplicationTest\View\Helper;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\View\Helper\SystemMessage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class SystemMessageTest extends MockeryTestCase
{
    public function testInvokeEmptySystemMessage(): void
    {
        $cache = Mockery::mock(DynamoDbKeyValueStore::class);
        $cache->shouldReceive('getItem')
                ->withArgs(['system-message'])->once()->andReturn("");

        $systemMessage = new SystemMessage($cache);
        $this->assertEquals('', $systemMessage());
    }

    public function testInvoke(): void
    {
        $cache = Mockery::mock(DynamoDbKeyValueStore::class);
        $cache->shouldReceive('getItem')
            ->withArgs(['system-message'])
            ->once()
            ->andReturn('  <script>alert("hello");</script>test message &   ');

        $systemMessage = new SystemMessage($cache);
        $actualMessage = $systemMessage();

        $this->assertStringContainsString('<i class="icon icon-important"></i>', $actualMessage);

        $expectedCleanedMessage = '<strong class="bold-small text">' .
            '&lt;script&gt;alert(&quot;hello&quot;);&lt;/script&gt;test message &amp;</strong>';
        $this->assertStringContainsString($expectedCleanedMessage, $actualMessage);
    }
}
