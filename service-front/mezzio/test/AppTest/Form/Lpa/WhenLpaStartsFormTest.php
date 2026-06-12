<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\WhenLpaStartsForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\TestCase;

final class WhenLpaStartsFormTest extends TestCase
{
    private WhenLpaStartsForm $form;

    protected function setUp(): void
    {
        $this->form = new WhenLpaStartsForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-when-lpa-starts', $this->form->getName());
    }

    public function testHasWhenRadioElement(): void
    {
        $this->assertTrue($this->form->has('when'));
        $this->assertInstanceOf(Radio::class, $this->form->get('when'));
    }

    public function testValidNowValueIsValid(): void
    {
        $this->form->setData(['when' => 'now']);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidNoCapacityValueIsValid(): void
    {
        $this->form->setData(['when' => 'no-capacity']);
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingWhenIsInvalid(): void
    {
        $this->form->setData([]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('when', $this->form->getMessages());
    }

    public function testEmptyWhenIsInvalid(): void
    {
        $this->form->setData(['when' => '']);
        $this->assertFalse($this->form->isValid());
    }
}
