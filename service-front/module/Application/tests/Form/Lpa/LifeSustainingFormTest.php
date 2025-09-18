<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\LifeSustainingForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class LifeSustainingFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp() : void
    {
        $this->setUpForm(new LifeSustainingForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\Lpa\LifeSustainingForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-life-sustaining', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('canSustainLife'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'canSustainLife' => true,
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'canSustainLife' => null,
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'canSustainLife' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ]
        ], $this->form->getMessages());
    }
}
