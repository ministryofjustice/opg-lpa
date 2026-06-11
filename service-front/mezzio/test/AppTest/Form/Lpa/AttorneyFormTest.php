<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\AttorneyForm;
use Laminas\Form\Element\Email;
use PHPUnit\Framework\TestCase;

final class AttorneyFormTest extends TestCase
{
    private AttorneyForm $form;

    protected function setUp(): void
    {
        $this->form = new AttorneyForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validAttorneyData(): array
    {
        return [
            'name-title'       => 'Ms',
            'name-first'       => 'Jane',
            'name-last'        => 'Smith',
            'dob-date'         => ['day' => '15', 'month' => '06', 'year' => '1985'],
            'email-address'    => '',
            'address-address1' => '2 Attorney Road',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'EC1A 1BB',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-attorney', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-attorney', $this->form->getAttribute('data-cy'));
    }

    public function testHasNameElements(): void
    {
        $this->assertTrue($this->form->has('name-title'));
        $this->assertTrue($this->form->has('name-first'));
        $this->assertTrue($this->form->has('name-last'));
    }

    public function testHasEmailAddressElement(): void
    {
        $this->assertTrue($this->form->has('email-address'));
        $this->assertInstanceOf(Email::class, $this->form->get('email-address'));
    }

    public function testHasAddressElements(): void
    {
        $this->assertTrue($this->form->has('address-address1'));
        $this->assertTrue($this->form->has('address-postcode'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData($this->validAttorneyData());
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingNameIsInvalid(): void
    {
        $data = $this->validAttorneyData();
        $data['name-first'] = '';
        $data['name-last']  = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testMissingAddressIsInvalid(): void
    {
        $data = $this->validAttorneyData();
        $data['address-address1'] = '';
        $data['address-postcode'] = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testEmailAddressIsLowercasedAfterValidation(): void
    {
        $data = $this->validAttorneyData();
        $data['email-address'] = 'JANE@EXAMPLE.COM';
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertSame('jane@example.com', $result['email-address']);
    }
}
