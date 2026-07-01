<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\PeopleToNotifyForm;
use PHPUnit\Framework\TestCase;

final class PeopleToNotifyFormTest extends TestCase
{
    private PeopleToNotifyForm $form;

    protected function setUp(): void
    {
        $this->form = new PeopleToNotifyForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return [
            'name-title'       => 'Mrs',
            'name-first'       => 'Carol',
            'name-last'        => 'White',
            'address-address1' => '4 Notify Lane',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'SE1 7PB',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-people-to-notify', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-people-to-notify', $this->form->getAttribute('data-cy'));
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
}
