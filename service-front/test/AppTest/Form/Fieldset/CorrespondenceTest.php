<?php

declare(strict_types=1);

namespace AppTest\Form\Fieldset;

use App\Form\Fieldset\Correspondence;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class CorrespondenceTest extends TestCase
{
    private Correspondence $fieldset;

    protected function setUp(): void
    {
        $this->fieldset = new Correspondence('correspondence');
    }

    public function testHasContactByEmailCheckbox(): void
    {
        $this->assertTrue($this->fieldset->has('contactByEmail'));
        $this->assertInstanceOf(Checkbox::class, $this->fieldset->get('contactByEmail'));
    }

    public function testHasContactByPhoneCheckbox(): void
    {
        $this->assertTrue($this->fieldset->has('contactByPhone'));
        $this->assertInstanceOf(Checkbox::class, $this->fieldset->get('contactByPhone'));
    }

    public function testHasContactByPostCheckbox(): void
    {
        $this->assertTrue($this->fieldset->has('contactByPost'));
        $this->assertInstanceOf(Checkbox::class, $this->fieldset->get('contactByPost'));
    }

    public function testHasEmailAddressElement(): void
    {
        $this->assertTrue($this->fieldset->has('email-address'));
        $this->assertInstanceOf(Email::class, $this->fieldset->get('email-address'));
    }

    public function testHasPhoneNumberElement(): void
    {
        $this->assertTrue($this->fieldset->has('phone-number'));
        $this->assertInstanceOf(Text::class, $this->fieldset->get('phone-number'));
    }

    public function testContactByEmailCheckedValueIsOne(): void
    {
        $checkbox = $this->fieldset->get('contactByEmail');
        $this->assertSame('1', $checkbox->getCheckedValue());
    }

    public function testContactByEmailUncheckedValueIsZero(): void
    {
        $checkbox = $this->fieldset->get('contactByEmail');
        $this->assertSame('0', $checkbox->getUncheckedValue());
    }

    public function testSetAndGetMessages(): void
    {
        $messages = ['contactByEmail' => ['error message']];
        $this->fieldset->setMessages($messages);
        $this->assertSame($messages, $this->fieldset->getMessages());
    }
}
