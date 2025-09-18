<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\ApplicantForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\DataModel\Lpa\Lpa;

final class ApplicantFormTest extends MockeryTestCase
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
                'primaryAttorneys' => [],
            ],
        ]);

        $form = new ApplicantForm(null, [
            'lpa' => $lpa,
        ]);

        $this->setUpForm($form);
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\ApplicantForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-applicant', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('whoIsRegistering'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'whoIsRegistering' => 'donor',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(
            array_merge(
                ['whoIsRegistering' => 'someoneelse'],
                $this->getCsrfData()
            )
        );

        $this->assertFalse($this->form->isValid());

        $this->assertEquals(
            ['whoIsRegistering' =>
                ['notInArray' => 'The input was not found in the haystack'],
            ],
            $this->form->getMessages()
        );
    }
}
