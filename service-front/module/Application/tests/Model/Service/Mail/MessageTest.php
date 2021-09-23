<?php

namespace ApplicationTest\Model\Service\Mail;

use Application\Model\Service\Mail\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @var Message
     */
    private $message;

    public function setUp(): void
    {
        $this->message = new Message();
    }

    public function testConstructor()
    {
        $this->assertEquals('UTF-8', $this->message->getEncoding());
    }

    public function testAddCategory()
    {
        $this->message->addCategory('Cat A');
        $this->message->addCategory('Cat B');

        $this->assertEquals(['Cat A', 'Cat B'], $this->message->getCategories());
    }
}
