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

    public function testWordLongerThan85CharsInInstructionIsInvalid(): void
    {
        $longWord = str_repeat('a', 86);
        $this->form->setData(['instruction' => "Some {$longWord} text", 'preference' => '']);
        $this->assertFalse($this->form->isValid());
    }

    public function testWordExactly85CharsInInstructionIsValid(): void
    {
        $exactWord = str_repeat('a', 85);
        $this->form->setData(['instruction' => "Some {$exactWord} text", 'preference' => '']);
        $this->assertTrue($this->form->isValid());
    }

    public function testWordLongerThan85CharsInPreferenceIsInvalid(): void
    {
        $longWord = str_repeat('b', 86);
        $this->form->setData(['instruction' => '', 'preference' => "Some {$longWord} text"]);
        $this->assertFalse($this->form->isValid());
    }

    public function testHttpLinkInInstructionIsInvalid(): void
    {
        $this->form->setData(['instruction' => 'See http://example.com for details', 'preference' => '']);
        $this->assertFalse($this->form->isValid());
    }

    public function testHttpsLinkInInstructionIsInvalid(): void
    {
        $this->form->setData(['instruction' => 'See https://example.com for details', 'preference' => '']);
        $this->assertFalse($this->form->isValid());
    }

    public function testHttpLinkInPreferenceIsInvalid(): void
    {
        $this->form->setData(['instruction' => '', 'preference' => 'See http://example.com for details']);
        $this->assertFalse($this->form->isValid());
    }

    public function testHttpsLinkInPreferenceIsInvalid(): void
    {
        $this->form->setData(['instruction' => '', 'preference' => 'See https://example.com for details']);
        $this->assertFalse($this->form->isValid());
    }
}
