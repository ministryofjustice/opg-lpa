<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\TrustCorporationForm;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class TrustCorporationFormTest extends TestCase
{
    private TrustCorporationForm $form;

    protected function setUp(): void
    {
        $this->form = new TrustCorporationForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validTrustData(): array
    {
        return [
            'name'             => 'ABC Trust Corporation Ltd',
            'number'           => '12345678',
            'email-address'    => '',
            'address-address1' => '1 Corporation Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'WC2N 5DU',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-trust-corporation', $this->form->getName());
    }

    public function testHasNameElement(): void
    {
        $this->assertTrue($this->form->has('name'));
        $this->assertInstanceOf(Text::class, $this->form->get('name'));
    }

    public function testHasNumberElement(): void
    {
        $this->assertTrue($this->form->has('number'));
        $this->assertInstanceOf(Text::class, $this->form->get('number'));
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
        $this->form->setData($this->validTrustData());
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingNameIsInvalid(): void
    {
        $data = $this->validTrustData();
        $data['name'] = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testMissingAddressIsInvalid(): void
    {
        $data = $this->validTrustData();
        $data['address-address1'] = '';
        $data['address-postcode'] = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }
}
