<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\RepeatApplicationForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class RepeatApplicationFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp() : void
    {
        $this->setUpMainFlowForm(new RepeatApplicationForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\RepeatApplicationForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-repeat-application', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('isRepeatApplication'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('repeatCaseNumber'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => 12345678,
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'isRepeatApplication' => '',
            'repeatCaseNumber'    => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'isRepeatApplication' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ],
            'repeatCaseNumber' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ]
        ], $this->form->getMessages());
    }
}
