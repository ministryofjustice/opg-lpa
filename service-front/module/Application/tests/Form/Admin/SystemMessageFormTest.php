<?php

namespace ApplicationTest\Form\Admin;

use Application\Form\Admin\SystemMessageForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SystemMessageFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpCsrfForm(new SystemMessageForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Admin\SystemMessageForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('admin-system-message', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Textarea', $this->form->get('message'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'message' => 'This is an admin message. ',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'message' => 'This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message. This is an admin message.',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'message' => [
                'stringLengthTooLong' => 'Limit the message to 8000 chars.'
            ],
        ], $this->form->getMessages());
    }
}
