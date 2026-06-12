<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\RepeatApplicationForm;
use PHPUnit\Framework\TestCase;

final class RepeatApplicationFormTest extends TestCase
{
    private RepeatApplicationForm $form;

    protected function setUp(): void
    {
        $this->form = new RepeatApplicationForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-repeat-application', $this->form->getName());
    }

    public function testNewApplicationWithValidCaseNumberIsValid(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-new',
            'repeatCaseNumber'    => '700000000001',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testRepeatApplicationWithValidTwelveDigitNumberIsValid(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => '700000000001',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testRepeatApplicationWithDashedNumberIsValid(): void
    {
        // The dash-to-separator filter should strip dashes
        $this->form->setData([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => '7000-0000-0001',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testRepeatApplicationWithTooShortNumberIsInvalid(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => '12345',
        ]);
        $this->assertFalse($this->form->isValid());
    }

    public function testRepeatApplicationWithTooLongNumberIsInvalid(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => '1234567890123',
        ]);
        $this->assertFalse($this->form->isValid());
    }

    public function testRepeatApplicationWithNonDigitsIsInvalid(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-repeat',
            'repeatCaseNumber'    => 'ABCDEFGHIJKL',
        ]);
        $this->assertFalse($this->form->isValid());
    }

    public function testNewApplicationWithEmptyCaseNumberIsInvalid(): void
    {
        // repeatCaseNumber is required at Laminas level regardless of radio selection;
        // validateByModel only skips model-level validation for is-new
        $this->form->setData([
            'isRepeatApplication' => 'is-new',
            'repeatCaseNumber'    => '',
        ]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('repeatCaseNumber', $this->form->getMessages());
    }

    public function testNewApplicationWithInvalidCaseNumberFailsLaminasValidation(): void
    {
        $this->form->setData([
            'isRepeatApplication' => 'is-new',
            'repeatCaseNumber'    => 'invalid',
        ]);
        $this->assertFalse($this->form->isValid());
    }
}
