<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\AbstractForm;
use App\Form\Lpa\TypeForm;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use PHPUnit\Framework\TestCase;

final class TypeFormTest extends TestCase
{
    private TypeForm $form;

    protected function setUp(): void
    {
        $this->form = new TypeForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-type', $this->form->getName());
    }

    public function testFormIsAnAbstractForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
    }

    public function testFormMethodIsPost(): void
    {
        $this->assertSame('post', $this->form->getAttribute('method'));
    }

    public function testHasTypeRadioElement(): void
    {
        $this->assertTrue($this->form->has('type'));
        $this->assertInstanceOf(Radio::class, $this->form->get('type'));
    }

    public function testHasSaveSubmitButton(): void
    {
        $this->assertTrue($this->form->has('save'));
        $this->assertInstanceOf(Submit::class, $this->form->get('save'));
    }

    public function testSaveButtonDefaultTextIsSaveAndContinue(): void
    {
        $this->assertSame('Save and continue', $this->form->get('save')->getValue());
    }

    public function testValidHealthAndWelfareTypeIsValid(): void
    {
        $this->form->setData(['type' => 'health-and-welfare']);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidPropertyAndFinancialTypeIsValid(): void
    {
        $this->form->setData(['type' => 'property-and-financial']);
        $this->assertTrue($this->form->isValid());
    }

    public function testEmptyTypeIsInvalid(): void
    {
        $this->form->setData(['type' => '']);
        $this->assertFalse($this->form->isValid());
        $messages = $this->form->getMessages();
        $this->assertArrayHasKey('type', $messages);
    }

    public function testMissingTypeIsInvalid(): void
    {
        $this->form->setData([]);
        $this->assertFalse($this->form->isValid());
        $messages = $this->form->getMessages();
        $this->assertArrayHasKey('type', $messages);
    }

    public function testInvalidTypeValueIsInvalid(): void
    {
        $this->form->setData(['type' => 'invalid-type']);
        $this->assertFalse($this->form->isValid());
    }
}
