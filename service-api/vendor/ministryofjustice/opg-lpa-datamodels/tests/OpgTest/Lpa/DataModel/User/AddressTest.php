<?php

namespace OpgTest\Lpa\DataModel\User;

use Opg\Lpa\DataModel\Common\Address;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $user = FixturesData::getUser();
        $address = $user->get('address');

        $this->assertEquals('12 Highway Close, PL45 9JA', '' . $address);
    }

    public function testValidation()
    {
        $user = FixturesData::getUser();
        /* @var $address \Opg\Lpa\DataModel\Common\Address */
        $address = $user->get('address');

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
}
