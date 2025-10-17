<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\WhoAreYouForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class WhoAreYouFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new WhoAreYouForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\Lpa\WhoAreYouForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-who-are-you', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $this->form->get('who'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('other'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'who'   => 'donor',
            'other' => '',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'who'   => '',
            'other' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'who' => [
                0 => 'cannot-be-blank'
            ]
        ], $this->form->getMessages());
    }
}
