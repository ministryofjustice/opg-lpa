<?php

namespace MakeSharedTest\DataModel\Common;

use MakeShared\DataModel\Common\Address;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testToString()
    {
        $donor = FixturesData::getDonor();
        $address = $donor->get('address');

        $this->assertEquals('562 Queen Street, Charlestown, Cornwall, England, EH9K 8UC', '' . $address);
    }

    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $address \MakeShared\DataModel\Common\Address */
        $address = $donor->get('address');

        $validatorResponse = $address->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $address = new Address();

        $validatorResponse = $address->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(2, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['address1']);
        $this->assertNotNull($errors['address2/postcode']);
    }

    public function testValidationFailedLength()
    {
        $address = new Address();
        $address->set('address1', FixturesData::generateRandomString(51));
        $address->set('address2', FixturesData::generateRandomString(51));
        $address->set('address3', FixturesData::generateRandomString(51));
        $address->set('postcode', FixturesData::generateRandomString(9));

        $validatorResponse = $address->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(4, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['address1']);
        $this->assertNotNull($errors['address2']);
        $this->assertNotNull($errors['address3']);
        $this->assertNotNull($errors['postcode']);
    }

     public function testLine1And2IsValid()
    {
        $address = new Address();
        $address->set('address1', FixturesData::generateRandomString(20));
        $address->set('address2', FixturesData::generateRandomString(20));
        $address->set('address3', '');
        $address->set('postcode', '');

        $validatorResponse = $address->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testLine1AndPostCodeIsValid()
    {
        $address = new Address();
        $address->set('address1', FixturesData::generateRandomString(20));
        $address->set('address2', '');
        $address->set('address3', '');
        $address->set('postcode', FixturesData::generateRandomString(7));

        $validatorResponse = $address->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testLineNoLine2orPostCodeInvalid()
    {
        $address = new Address();
        $address->set('address1', FixturesData::generateRandomString(20));
        $address->set('address2', '');
        $address->set('address3', FixturesData::generateRandomString(20));
        $address->set('postcode', '');

        $validatorResponse = $address->validate();
        $this->assertTrue($validatorResponse->hasErrors());

        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['address2/postcode']);
    }

    public function testGetsAndSets()
    {
        $model = new Address();

        $model->setAddress1('address1')
            ->setAddress2('address2')
            ->setAddress3('address3')
            ->setPostcode('postcode');

        $this->assertEquals('address1', $model->getAddress1());
        $this->assertEquals('address2', $model->getAddress2());
        $this->assertEquals('address3', $model->getAddress3());
        $this->assertEquals('postcode', $model->getPostcode());
    }
}
