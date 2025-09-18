<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\ReuseDetailsForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ReuseDetailsFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new ReuseDetailsForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\ReuseDetailsForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-reuse-details', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('reuse-details'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'reuse-details' => '1',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'reuse-details' => null,
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'reuse-details' => [
                0 => 'cannot-be-empty'
            ]
        ], $this->form->getMessages());
    }
}
