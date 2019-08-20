<?php

namespace ApplicationTest\Form\General;

use Application\Form\General\FeedbackForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class FeedbackFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpCsrfForm(new FeedbackForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\General\FeedbackForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('send-feedback', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Radio', $this->form->get('rating'));
        $this->assertInstanceOf('Zend\Form\Element\Textarea', $this->form->get('details'));
        $this->assertInstanceOf('Zend\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('phone'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'rating' => 'very-satisfied',
            'details' => 'Feedback message here',
            'email' => 'a@b.com',
            'phone' => '01234 123456',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'rating' => 'indifferent',
            'details' => 'Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long. Feedback message too long.',
            'email' => 'notanemail',
            'phone' => 'jisofjisd',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                0 => 'Enter a valid email address'
            ],
            'details' => [
                'stringLengthTooLong' => 'max-2000-chars'
            ],
        ], $this->form->getMessages());
    }
}
