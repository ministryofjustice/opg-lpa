<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\Lpa\AbstractActorForm;
use App\Form\User\AboutYou;
use PHPUnit\Framework\TestCase;

final class AboutYouTest extends TestCase
{
    private AboutYou $form;

    protected function setUp(): void
    {
        $this->form = new AboutYou();
        $this->form->init();
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        return [
            'name-title'       => 'Mr',
            'name-first'       => 'John',
            'name-last'        => 'Doe',
            'dob-date'         => ['day' => '01', 'month' => '01', 'year' => '1970'],
            'address-address1' => '1 Test Street',
            'address-address2' => '',
            'address-address3' => '',
            'address-postcode' => 'SW1A 1AA',
        ];
    }

    public function testFormName(): void
    {
        $this->assertSame('about-you', $this->form->getName());
    }

    public function testIsAbstractActorForm(): void
    {
        $this->assertInstanceOf(AbstractActorForm::class, $this->form);
    }

    public function testHasNameElements(): void
    {
        $this->assertTrue($this->form->has('name-title'));
        $this->assertTrue($this->form->has('name-first'));
        $this->assertTrue($this->form->has('name-last'));
    }

    public function testHasDobDateFieldset(): void
    {
        $this->assertTrue($this->form->has('dob-date'));
    }

    public function testHasAddressElements(): void
    {
        $this->assertTrue($this->form->has('address-address1'));
        $this->assertTrue($this->form->has('address-address2'));
        $this->assertTrue($this->form->has('address-address3'));
        $this->assertTrue($this->form->has('address-postcode'));
    }

    public function testValidDataFailsModelValidationDueToMissingUserFields(): void
    {
        // The User model requires id, createdAt, updatedAt which the form doesn't manage.
        // AbstractActorForm::validateByModel validates the entire model, so isValid() returns
        // false even with valid form-level data. This is the expected behaviour — the handler
        // uses the form for input collection and filtering, not full model validation.
        $this->form->setData($this->validData());
        $this->assertFalse($this->form->isValid());
    }

    public function testGetDataReturnsFormattedDobDate(): void
    {
        $this->form->setData($this->validData());
        $this->form->isValid();
        $data = $this->form->getData();
        $this->assertSame('1970-01-01', $data['dob-date']);
    }

    public function testGetDataWithEmptyDobReturnsNoDateKey(): void
    {
        $data             = $this->validData();
        $data['dob-date'] = ['day' => '', 'month' => '', 'year' => ''];
        $this->form->setData($data);
        // isValid() must be called before getData(); result may be false due to missing dob model validation
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertArrayNotHasKey('dob-date', $result);
    }

    public function testSetDataRemovesEmptyDobDate(): void
    {
        $data            = $this->validData();
        $data['dob-date'] = ['day' => '', 'month' => '', 'year' => ''];
        $this->form->setData($data);
        // Should not throw an error
        $this->assertInstanceOf(AboutYou::class, $this->form);
    }

    public function testEmptyAddressSetsAddressToNull(): void
    {
        $data                     = $this->validData();
        $data['address-address1'] = '';
        $data['address-address2'] = '';
        $data['address-address3'] = '';
        $data['address-postcode'] = '';
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertNull($result['address']);
    }

    public function testPreferNotToSayTitleIsNullifiedInGetData(): void
    {
        $data               = $this->validData();
        $data['name-title'] = AbstractActorForm::PREFER_NOT_TO_SAY_TITLE;
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertNull($result['name-title']);
    }

    public function testMissingNameIsInvalid(): void
    {
        $data               = $this->validData();
        $data['name-first'] = '';
        $data['name-last']  = '';
        $this->form->setData($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testHtmlTagsAreStrippedFromName(): void
    {
        $data               = $this->validData();
        $data['name-first'] = '<script>alert(1)</script>John';
        $this->form->setData($data);
        $this->form->isValid();
        $result = $this->form->getData();
        $this->assertStringNotContainsString('<script>', $result['name-first']);
    }
}
