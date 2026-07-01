<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\CorrespondentForm;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use PHPUnit\Framework\TestCase;

final class CorrespondentFormTest extends TestCase
{
    private CorrespondentForm $form;

    protected function setUp(): void
    {
        $this->form = new CorrespondentForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-correspondent', $this->form->getName());
    }

    public function testDataCyAttribute(): void
    {
        $this->assertSame('form-correspondent', $this->form->getAttribute('data-cy'));
    }

    public function testIsEditableByDefault(): void
    {
        $this->assertTrue($this->form->isEditable());
    }

    public function testTrustNotSelectedByDefault(): void
    {
        $this->assertFalse($this->form->trustSelected());
    }

    public function testBindWithDonorSetsNotEditable(): void
    {
        $this->form->bind([
            'who'  => Correspondence::WHO_DONOR,
            'type' => 'human',
        ]);
        $this->assertFalse($this->form->isEditable());
    }

    public function testBindWithCertificateProviderSetsNotEditable(): void
    {
        $this->form->bind([
            'who'  => Correspondence::WHO_CERTIFICATE_PROVIDER,
            'type' => 'human',
        ]);
        $this->assertFalse($this->form->isEditable());
    }

    public function testBindWithHumanAttorneySetsNotEditable(): void
    {
        $this->form->bind([
            'who'  => Correspondence::WHO_ATTORNEY,
            'type' => 'human',
        ]);
        $this->assertFalse($this->form->isEditable());
    }

    public function testBindWithOtherCorrespondentRemainsEditable(): void
    {
        $this->form->bind([
            'who'  => Correspondence::WHO_OTHER,
            'type' => 'human',
        ]);
        $this->assertTrue($this->form->isEditable());
    }

    public function testBindWithTrustTypeSetsTrustSelected(): void
    {
        $this->form->bind([
            'who'     => Correspondence::WHO_ATTORNEY,
            'type'    => 'trust',
            'name'    => 'Corp Ltd',
            'company' => 'Corp Ltd',
        ]);
        $this->assertTrue($this->form->trustSelected());
    }

    public function testBindWithTrustTypeCopiesNameToCompany(): void
    {
        $this->form->bind([
            'who'  => Correspondence::WHO_ATTORNEY,
            'type' => 'trust',
            'name' => 'Corp Ltd',
        ]);
        // The 'name' key is removed; company is set instead
        $this->assertTrue($this->form->trustSelected());
    }

    public function testHasNameAndAddressElements(): void
    {
        $this->assertTrue($this->form->has('name-title'));
        $this->assertTrue($this->form->has('name-first'));
        $this->assertTrue($this->form->has('name-last'));
        $this->assertTrue($this->form->has('address-address1'));
        $this->assertTrue($this->form->has('address-postcode'));
    }

    public function testHasEmailAndPhoneElements(): void
    {
        $this->assertTrue($this->form->has('email-address'));
        $this->assertTrue($this->form->has('phone-number'));
    }

    public function testIsValidHandlesReuseDataWithNullNestedFields(): void
    {
        $reuseData = [
            'who'              => Correspondence::WHO_OTHER,
            'name-title'       => 'Mr',
            'name-first'       => 'Bob',
            'name-last'        => 'Smith',
            'address-address1' => '1 High Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'AB1 2CD',
            'email'            => null,
            'phone'            => null,
        ];

        $this->form->bind($reuseData);

        $this->form->isValid();

        $modelData = $this->form->getModelDataFromValidatedForm();

        $this->assertIsArray($modelData);
        $this->assertSame('Bob', $modelData['name']['first'] ?? null);
        $this->assertSame('Smith', $modelData['name']['last'] ?? null);
        $this->assertSame('1 High Street', $modelData['address']['address1'] ?? null);
    }

    public function testIsValidPreservesNestedValueWhenFlatCollisionExists(): void
    {
        $reuseData = [
            'who'              => Correspondence::WHO_OTHER,
            'name-title'       => 'Mr',
            'name-first'       => 'Bob',
            'name-last'        => 'Smith',
            'address-address1' => '1 High Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'AB1 2CD',
            'email-address'    => 'bob@example.com',
            'email'            => null,
        ];

        $this->form->bind($reuseData);
        $this->form->isValid();

        $modelData = $this->form->getModelDataFromValidatedForm();

        $this->assertIsArray($modelData);
        $this->assertSame('bob@example.com', $modelData['email']['address'] ?? null);
    }
}
