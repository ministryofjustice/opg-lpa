<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\DonorForm;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class DonorFormTest extends TestCase
{
    private DonorForm $form;

    protected function setUp(): void
    {
        $this->form = new DonorForm();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validDonorData(): array
    {
        return [
            'name-title'       => 'Mr',
            'name-first'       => 'John',
            'name-last'        => 'Doe',
            'otherNames'       => '',
            'dob-date'         => ['day' => '01', 'month' => '01', 'year' => '1970'],
            'email-address'    => '',
            'address-address1' => '1 Test Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'SW1A 1AA',
            'cannotSign'       => '0',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('form-donor', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-donor', $this->form->getAttribute('data-cy'));
    }

    public function testHasNameTitleElement(): void
    {
        $this->assertTrue($this->form->has('name-title'));
        $this->assertInstanceOf(Text::class, $this->form->get('name-title'));
    }

    public function testHasNameFirstElement(): void
    {
        $this->assertTrue($this->form->has('name-first'));
    }

    public function testHasNameLastElement(): void
    {
        $this->assertTrue($this->form->has('name-last'));
    }

    public function testHasEmailAddressElement(): void
    {
        $this->assertTrue($this->form->has('email-address'));
        $this->assertInstanceOf(Email::class, $this->form->get('email-address'));
    }

    public function testHasCannotSignCheckbox(): void
    {
        $this->assertTrue($this->form->has('cannotSign'));
        $this->assertInstanceOf(Checkbox::class, $this->form->get('cannotSign'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData($this->validDonorData());
        $this->assertTrue($this->form->isValid());
    }

    public function testMissingNameIsInvalid(): void
    {
        $data = $this->validDonorData();
        $data['name-first'] = '';
        $data['name-last']  = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testMissingAddressIsInvalid(): void
    {
        $data = $this->validDonorData();
        $data['address-address1'] = '';
        $data['address-postcode'] = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testCannotSignOneSetsDonorCannotSign(): void
    {
        $data = $this->validDonorData();
        $data['cannotSign'] = '1';
        $this->form->setData($data);
        $this->form->isValid();

        $modelData = $this->form->getModelDataFromValidatedForm();
        $this->assertFalse($modelData['canSign']);
    }

    public function testCannotSignZeroSetsDonorCanSign(): void
    {
        $this->form->setData($this->validDonorData());
        $this->form->isValid();

        $modelData = $this->form->getModelDataFromValidatedForm();
        $this->assertTrue($modelData['canSign']);
    }

    public function testPopulateValuesWithCanSignFalseSetsCannotSignToOne(): void
    {
        $this->form->populateValues(['canSign' => false, 'name-first' => 'Jane']);
        $this->assertSame('1', $this->form->get('cannotSign')->getValue());
    }

    public function testPopulateValuesWithCanSignTrueSetsCannotSignToZero(): void
    {
        $this->form->populateValues(['canSign' => true, 'name-first' => 'Jane']);
        $this->assertSame('0', $this->form->get('cannotSign')->getValue());
    }

    public function testHtmlTagsAreStrippedFromTextFields(): void
    {
        $data = $this->validDonorData();
        $data['name-first'] = '<b>John</b>';
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertSame('John', $result['name-first']);
    }

    public function testTextFieldsAreTrimmed(): void
    {
        $data = $this->validDonorData();
        $data['name-first'] = '  John  ';
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertSame('John', $result['name-first']);
    }

    public function testGetModelDataFromValidatedFormHandlesReuseDataWithNullNestedFields(): void
    {
        $reuseData = [
            'name-title'       => 'Mr',
            'name-first'       => 'John',
            'name-last'        => 'Doe',
            'otherNames'       => '',
            'dob-date'         => ['day' => '01', 'month' => '01', 'year' => '1970'],
            'email-address'    => 'john@example.com',
            'address-address1' => '1 Test Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'SW1A 1AA',
            'cannotSign'       => '0',
            'email'            => null,
        ];

        $this->form->bind($reuseData);
        $this->form->isValid();

        $modelData = $this->form->getModelDataFromValidatedForm();

        $this->assertIsArray($modelData);
        $this->assertSame('John', $modelData['name']['first'] ?? null);
        $this->assertSame('Doe', $modelData['name']['last'] ?? null);
        $this->assertSame('john@example.com', $modelData['email']['address'] ?? null);
    }
}
