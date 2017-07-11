<?php

namespace OpgTest\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\Common\Name;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;

class NameTest extends \PHPUnit_Framework_TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $name \Opg\Lpa\DataModel\Common\Name */
        $name = $donor->get('name');

        $validatorResponse = $name->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $name = new Name();
        $name->set('title', FixturesData::generateRandomString(6));
        $name->set('first', FixturesData::generateRandomString(51));
        $name->set('last', FixturesData::generateRandomString(51));

        $validatorResponse = $name->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['title']);
        $this->assertNotNull($errors['first']);
        $this->assertNotNull($errors['last']);
    }

    public function testToString()
    {
        $donor = FixturesData::getDonor();
        $name = $donor->get('name');

        $this->assertEquals('Hon Ayden Armstrong', '' . $name);
    }
}
