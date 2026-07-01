<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\CertificateProviderForm;
use PHPUnit\Framework\TestCase;

final class CertificateProviderFormTest extends TestCase
{
    private CertificateProviderForm $form;

    protected function setUp(): void
    {
        $this->form = new CertificateProviderForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return [
            'name-title'       => 'Dr',
            'name-first'       => 'Alice',
            'name-last'        => 'Green',
            'address-address1' => '3 Cert Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'N1 9GU',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-certificate-provider', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-certificate-provider', $this->form->getAttribute('data-cy'));
    }

    public function testHasNameElements(): void
    {
        $this->assertTrue($this->form->has('name-title'));
        $this->assertTrue($this->form->has('name-first'));
        $this->assertTrue($this->form->has('name-last'));
    }

    public function testHasAddressElements(): void
    {
        $this->assertTrue($this->form->has('address-address1'));
        $this->assertTrue($this->form->has('address-postcode'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData($this->validData());
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingNameIsInvalid(): void
    {
        $data = $this->validData();
        $data['name-first'] = '';
        $data['name-last']  = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testMissingAddressIsInvalid(): void
    {
        $data = $this->validData();
        $data['address-address1'] = '';
        $data['address-postcode'] = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }
}
