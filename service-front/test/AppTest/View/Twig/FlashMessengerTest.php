<?php

declare(strict_types=1);

namespace AppTest\View\Twig;

use App\Model\FlashMessagesHolder;
use App\View\Twig\FlashMessenger;
use Mezzio\Flash\FlashMessagesInterface;
use PHPUnit\Framework\TestCase;

final class FlashMessengerTest extends TestCase
{
    private FlashMessagesHolder $holder;
    private FlashMessenger $flashMessenger;

    protected function setUp(): void
    {
        $this->holder = new FlashMessagesHolder();
        $this->flashMessenger = new FlashMessenger($this->holder);
    }

    public function testGetMessagesReturnsEmptyArrayWhenNoFlashIsSet(): void
    {
        $this->assertSame([], $this->flashMessenger->getMessages('error'));
    }

    public function testGetMessagesReturnsArrayOfStringsWhenFlashReturnsArray(): void
    {
        $flash = $this->getMockBuilder(FlashMessagesInterface::class)
            ->addMethods(['getFlash'])
            ->getMock();
        $flash->expects($this->once())
            ->method('getFlash')
            ->with('flash_error')
            ->willReturn(['first message', 2]);
        $this->holder->set($flash);

        $this->assertSame(['first message', '2'], $this->flashMessenger->getMessages('error'));
    }

    public function testGetMessagesReturnsEmptyArrayWhenFlashReturnsNull(): void
    {
        $flash = $this->getMockBuilder(FlashMessagesInterface::class)
            ->addMethods(['getFlash'])
            ->getMock();
        $flash->expects($this->once())
            ->method('getFlash')
            ->with('flash_error')
            ->willReturn(null);
        $this->holder->set($flash);

        $this->assertSame([], $this->flashMessenger->getMessages('error'));
    }

    public function testGetMessagesWrapsSingleStringValueInArray(): void
    {
        $flash = $this->getMockBuilder(FlashMessagesInterface::class)
            ->addMethods(['getFlash'])
            ->getMock();
        $flash->expects($this->once())
            ->method('getFlash')
            ->with('flash_error')
            ->willReturn('single message');
        $this->holder->set($flash);

        $this->assertSame(['single message'], $this->flashMessenger->getMessages('error'));
    }

    public function testUnknownTypeUsesFlashTypeConvention(): void
    {
        $flash = $this->getMockBuilder(FlashMessagesInterface::class)
            ->addMethods(['getFlash'])
            ->getMock();
        $flash->expects($this->once())
            ->method('getFlash')
            ->with('flash_custom')
            ->willReturn('custom message');
        $this->holder->set($flash);

        $this->assertSame(['custom message'], $this->flashMessenger->getMessages('custom'));
    }
}
