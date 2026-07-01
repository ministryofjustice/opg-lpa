<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\WhenReplacementAttorneyStepInForm;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Textarea;
use PHPUnit\Framework\TestCase;

final class WhenReplacementAttorneyStepInFormTest extends TestCase
{
    private WhenReplacementAttorneyStepInForm $form;

    protected function setUp(): void
    {
        $this->form = new WhenReplacementAttorneyStepInForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-when-replacement-attonrey-step-in', $this->form->getName());
    }

    public function testHasWhenRadioElement(): void
    {
        $this->assertTrue($this->form->has('when'));
        $this->assertInstanceOf(Radio::class, $this->form->get('when'));
    }

    public function testHasWhenDetailsTextarea(): void
    {
        $this->assertTrue($this->form->has('whenDetails'));
        $this->assertInstanceOf(Textarea::class, $this->form->get('whenDetails'));
    }

    public function testFirstIsValid(): void
    {
        $this->form->setData(['when' => 'first', 'whenDetails' => 'some details']);
        $this->assertTrue($this->form->isValid());
    }

    public function testLastIsValid(): void
    {
        $this->form->setData(['when' => 'last', 'whenDetails' => 'some details']);
        $this->assertTrue($this->form->isValid());
    }

    public function testDependsIsValid(): void
    {
        $this->form->setData(['when' => 'depends', 'whenDetails' => 'details here']);
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingWhenIsInvalid(): void
    {
        $this->form->setData(['when' => '', 'whenDetails' => 'some details']);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('when', $this->form->getMessages());
    }

    public function testEmptyWhenDetailsIsInvalid(): void
    {
        $this->form->setData(['when' => 'first', 'whenDetails' => '']);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('whenDetails', $this->form->getMessages());
    }
}
