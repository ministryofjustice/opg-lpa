<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\HowAttorneysMakeDecisionForm;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Textarea;
use PHPUnit\Framework\TestCase;

final class HowAttorneysMakeDecisionFormTest extends TestCase
{
    private HowAttorneysMakeDecisionForm $form;

    protected function setUp(): void
    {
        $this->form = new HowAttorneysMakeDecisionForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-primary-attorney-decisions', $this->form->getName());
    }

    public function testHasHowRadioElement(): void
    {
        $this->assertTrue($this->form->has('how'));
        $this->assertInstanceOf(Radio::class, $this->form->get('how'));
    }

    public function testHasHowDetailsTextarea(): void
    {
        $this->assertTrue($this->form->has('howDetails'));
        $this->assertInstanceOf(Textarea::class, $this->form->get('howDetails'));
    }

    public function testJointlyAndSeverallyIsValid(): void
    {
        $this->form->setData(['how' => 'jointly-attorney-severally', 'howDetails' => 'some details']);
        $this->assertTrue($this->form->isValid());
    }

    public function testJointlyIsValid(): void
    {
        $this->form->setData(['how' => 'jointly', 'howDetails' => 'some details']);
        $this->assertTrue($this->form->isValid());
    }

    public function testDependsIsValid(): void
    {
        $this->form->setData(['how' => 'depends', 'howDetails' => 'some details']);
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingHowIsInvalid(): void
    {
        $this->form->setData(['how' => '', 'howDetails' => 'some details']);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('how', $this->form->getMessages());
    }

    public function testEmptyHowDetailsIsInvalid(): void
    {
        $this->form->setData(['how' => 'jointly', 'howDetails' => '']);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('howDetails', $this->form->getMessages());
    }
}
