<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\InstructionsAndPreferencesForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class InstructionsAndPreferencesFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp() : void
    {
        $this->setUpForm(new InstructionsAndPreferencesForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\InstructionsAndPreferencesForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-preferences-and-instructions', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Textarea', $this->form->get('instruction'));
        $this->assertInstanceOf('Laminas\Form\Element\Textarea', $this->form->get('preference'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'instruction' => 'Some instructions here.',
            'preference'  => 'Some preferences here.'
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInstructionsTooLong()
    {
        $this->form->setData(array_merge([
            'instruction' => str_repeat('a', 10001),
            'preference'  => 'Some preferences here.'
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals(['instruction' => [0 => 'must-be-less-than-or-equal:10000']], $this->form->getMessages());
    }

    public function testValidateByModelInstructionsInvalidType()
    {
        $this->form->setData(array_merge([
            'instruction' => 10,
            'preference'  => 'Some preferences here.'
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals(['instruction' => [0 => 'expected-type:string-or-bool=false']], $this->form->getMessages());
    }

    public function testValidateByModelPreferenceTooLong()
    {
        $this->form->setData(array_merge([
            'instruction' => 'Some instructions here.',
            'preference'  => str_repeat('a', 10001)
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals(['preference' => [0 => 'must-be-less-than-or-equal:10000']], $this->form->getMessages());
    }

    public function testValidateByModelPreferenceInvalidType()
    {
        $this->form->setData(array_merge([
            'instruction' => 'Some instructions here.',
            'preference'  => 10
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals(['preference' => [0 => 'expected-type:string-or-bool=false']], $this->form->getMessages());
    }
}
