<?php

declare(strict_types=1);

namespace AppTest\Form\Fieldset;

use App\Form\Fieldset\Dob;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class DobTest extends TestCase
{
    private Dob $fieldset;

    protected function setUp(): void
    {
        $this->fieldset = new Dob('dob-date');
    }

    public function testHasDayElement(): void
    {
        $this->assertTrue($this->fieldset->has('day'));
        $this->assertInstanceOf(Text::class, $this->fieldset->get('day'));
    }

    public function testHasMonthElement(): void
    {
        $this->assertTrue($this->fieldset->has('month'));
        $this->assertInstanceOf(Text::class, $this->fieldset->get('month'));
    }

    public function testHasYearElement(): void
    {
        $this->assertTrue($this->fieldset->has('year'));
        $this->assertInstanceOf(Text::class, $this->fieldset->get('year'));
    }

    public function testSetAndGetMessages(): void
    {
        $messages = ['day' => ['Value is required']];
        $this->fieldset->setMessages($messages);
        $this->assertSame($messages, $this->fieldset->getMessages());
    }

    public function testGetMessagesWithElementNameReturnsAllMessages(): void
    {
        $messages = ['year' => ['Invalid year']];
        $this->fieldset->setMessages($messages);
        $this->assertSame($messages, $this->fieldset->getMessages('year'));
    }

    public function testFieldsetName(): void
    {
        $this->assertSame('dob-date', $this->fieldset->getName());
    }
}
