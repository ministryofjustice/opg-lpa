<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\LifeSustainingForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\TestCase;

final class LifeSustainingFormTest extends TestCase
{
    private LifeSustainingForm $form;

    protected function setUp(): void
    {
        $this->form = new LifeSustainingForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-life-sustaining', $this->form->getName());
    }

    public function testHasCanSustainLifeRadioElement(): void
    {
        $this->assertTrue($this->form->has('canSustainLife'));
        $this->assertInstanceOf(Radio::class, $this->form->get('canSustainLife'));
    }

    public function testTrueValueIsValid(): void
    {
        $this->form->setData(['canSustainLife' => '1']);
        $this->assertTrue($this->form->isValid());
    }

    public function testFalseValueIsValid(): void
    {
        $this->form->setData(['canSustainLife' => '0']);
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingValueIsInvalid(): void
    {
        $this->form->setData([]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('canSustainLife', $this->form->getMessages());
    }
}
