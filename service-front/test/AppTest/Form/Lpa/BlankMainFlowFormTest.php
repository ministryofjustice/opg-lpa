<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\BlankMainFlowForm;
use Laminas\Form\Element\Submit;
use PHPUnit\Framework\TestCase;

final class BlankMainFlowFormTest extends TestCase
{
    private BlankMainFlowForm $form;

    protected function setUp(): void
    {
        $this->form = new BlankMainFlowForm();
        $this->form->init();
    }

    public function testHasSubmitElement(): void
    {
        $this->assertTrue($this->form->has('submit'));
        $this->assertInstanceOf(Submit::class, $this->form->get('submit'));
    }

    public function testSubmitElementValue(): void
    {
        $this->assertSame('Save and continue', $this->form->get('submit')->getValue());
    }

    public function testEmptyDataIsValid(): void
    {
        $this->form->setData([]);
        $this->assertTrue($this->form->isValid());
    }

    public function testFormMethodIsPost(): void
    {
        $this->assertSame('post', $this->form->getAttribute('method'));
    }
}
