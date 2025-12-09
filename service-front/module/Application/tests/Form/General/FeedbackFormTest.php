<?php

declare(strict_types=1);

namespace ApplicationTest\Form\General;

use Application\Form\General\FeedbackForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class FeedbackFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new FeedbackForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\General\FeedbackForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('send-feedback', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('rating'));
        $this->assertInstanceOf('Laminas\Form\Element\Textarea', $this->form->get('details'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('phone'));
    }

    public function testValidateByModelOK(): void
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

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'rating' => 'indifferent',
            'details' => str_repeat('a', 2001),
            'email' => 'notanemail',
            'phone' => 'jisofjisd',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                'invalidEmailAddress' => 'Enter a valid email address'
            ],
            'details' => [
                'stringLengthTooLong' => 'max-2000-chars'
            ],
            'phone' => [
                'notPhone' => 'Enter a valid phone number'
            ],
        ], $this->form->getMessages());
    }

    public function testDetailsFieldStripsTags(): void
    {
        $this->form->setData(array_merge([
            'rating' => 'very-satisfied',
            'email' => 'a@b.com',
            'details' => '<p>This is some feedback with <b>HTML</b> tags.  </p> ',
        ], $this->getCsrfData()));
        $this->assertTrue($this->form->isValid());

        $filteredData = $this->form->getData();
        $this->assertEquals('This is some feedback with HTML tags.', $filteredData['details']);
    }
}
