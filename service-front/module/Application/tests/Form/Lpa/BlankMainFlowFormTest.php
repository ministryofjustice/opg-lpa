<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\BlankMainFlowForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class BlankMainFlowFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new BlankMainFlowForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\Lpa\BlankMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData($this->getCsrfData());

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }
}
