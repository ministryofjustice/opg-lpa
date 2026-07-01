<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\InstructionsAndPreferencesForm;
use Laminas\Form\Element\Textarea;
use PHPUnit\Framework\TestCase;

final class InstructionsAndPreferencesFormTest extends TestCase
{
    private InstructionsAndPreferencesForm $form;

    protected function setUp(): void
    {
        $this->form = new InstructionsAndPreferencesForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-preferences-and-instructions', $this->form->getName());
    }

    public function testHasInstructionTextarea(): void
    {
        $this->assertTrue($this->form->has('instruction'));
        $this->assertInstanceOf(Textarea::class, $this->form->get('instruction'));
    }

    public function testHasPreferenceTextarea(): void
    {
        $this->assertTrue($this->form->has('preference'));
        $this->assertInstanceOf(Textarea::class, $this->form->get('preference'));
    }

    public function testBothEmptyIsValid(): void
    {
        $this->form->setData(['instruction' => '', 'preference' => '']);
        $this->assertTrue($this->form->isValid());
    }

    public function testWithInstructionAndPreferenceIsValid(): void
    {
        $this->form->setData([
            'instruction' => 'My instruction',
            'preference'  => 'My preference',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testHtmlTagsInInstructionAreStripped(): void
    {
        $this->form->setData([
            'instruction' => '<b>My instruction</b>',
            'preference'  => '',
        ]);
        $this->form->isValid();
        $data = $this->form->getData();
        $this->assertStringNotContainsString('<b>', $data['instruction']);
        $this->assertStringContainsString('My instruction', $data['instruction']);
    }
}
