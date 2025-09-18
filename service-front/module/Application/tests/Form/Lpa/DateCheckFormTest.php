<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\DateCheckForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;

final class DateCheckFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        //  Set up the form with the LPA data
        $lpa = new Lpa([
            'document' => [
                'type'              => Document::LPA_TYPE_HW,
                'primaryAttorneys'  => [],
            ],
        ]);

        $form = new DateCheckForm(null, [
            'lpa' => $lpa,
        ]);

        $this->setUpForm($form);
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\DateCheckForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-date-checker', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('sign-date-donor-life-sustaining'));
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('sign-date-donor'));
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('sign-date-certificate-provider'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'sign-date-donor-life-sustaining' => [
                'day'   => '01',
                'month' => '09',
                'year'  => '2018'
            ],
            'sign-date-donor' => [
                'day'   => '01',
                'month' => '09',
                'year'  => '2018'
            ],
            'sign-date-certificate-provider' => [
                'day'   => '01',
                'month' => '09',
                'year'  => '2018'
            ],
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'sign-date-donor-life-sustaining' => 'not-date',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'sign-date-donor-life-sustaining' => [
                'dateInvalidDate' => 'The input does not appear to be a valid date'
            ],
            'sign-date-donor' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ],
            'sign-date-certificate-provider' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ]
        ], $this->form->getMessages());
    }
}
