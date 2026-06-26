<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\CorrespondenceForm;
use PHPUnit\Framework\TestCase;

final class CorrespondenceFormTest extends TestCase
{
    private CorrespondenceForm $form;

    protected function setUp(): void
    {
        $this->form = new CorrespondenceForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validDataPostOnly(): array
    {
        return [
            'contactInWelsh' => '0',
            'correspondence' => [
                'contactByPost'  => '1',
                'contactByPhone' => '0',
                'contactByEmail' => '0',
                'phone-number'   => '',
                'email-address'  => '',
            ],
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-correspondence', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-correspondence', $this->form->getAttribute('data-cy'));
    }

    public function testHasContactInWelshElement(): void
    {
        $this->assertTrue($this->form->has('contactInWelsh'));
    }

    public function testHasCorrespondenceFieldset(): void
    {
        $this->assertTrue($this->form->has('correspondence'));
    }

    public function testValidPostOnlyDataIsValid(): void
    {
        $this->form->setData($this->validDataPostOnly());
        $this->assertTrue($this->form->isValid());
    }

    public function testValidEmailContactIsValid(): void
    {
        $data = $this->validDataPostOnly();
        $data['correspondence']['contactByEmail'] = '1';
        $data['correspondence']['email-address']  = 'test@example.com';
        $this->form->setData($data);
        $this->assertTrue($this->form->isValid());
    }

    public function testEmailContactWithInvalidEmailIsInvalid(): void
    {
        $data = $this->validDataPostOnly();
        $data['correspondence']['contactByEmail'] = '1';
        $data['correspondence']['email-address']  = 'not-an-email';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testEmailContactWithBlankEmailIsInvalid(): void
    {
        $data = $this->validDataPostOnly();
        $data['correspondence']['contactByEmail'] = '1';
        $data['correspondence']['email-address']  = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testValidPhoneContactIsValid(): void
    {
        $data = $this->validDataPostOnly();
        $data['correspondence']['contactByPhone'] = '1';
        $data['correspondence']['phone-number']   = '01234567890';
        $this->form->setData($data);
        $this->assertTrue($this->form->isValid());
    }

    public function testPhoneContactWithBlankNumberIsInvalid(): void
    {
        $data = $this->validDataPostOnly();
        $data['correspondence']['contactByPhone'] = '1';
        $data['correspondence']['phone-number']   = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testWelshContactIsValid(): void
    {
        $data = $this->validDataPostOnly();
        $data['contactInWelsh'] = '1';
        $this->form->setData($data);
        $this->assertTrue($this->form->isValid());
    }
}
