<?php

declare(strict_types=1);

namespace AppTest\Model;

use App\Model\FlashMessagesHolder;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\TestCase;

class FlashMessagesHolderTest extends TestCase
{
    public function testGetReturnsNullByDefault(): void
    {
        $this->assertNull((new FlashMessagesHolder())->get());
    }

    public function testSetStoresFlashMessages(): void
    {
        $flashMessages = $this->createMock(FlashMessagesInterface::class);
        $holder = new FlashMessagesHolder();

        $holder->set($flashMessages);

        $this->assertSame($flashMessages, $holder->get());
    }
}
