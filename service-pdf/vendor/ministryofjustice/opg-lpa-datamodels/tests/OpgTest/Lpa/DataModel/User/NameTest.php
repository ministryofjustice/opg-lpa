<?php

namespace OpgTest\Lpa\DataModel\User;

use Opg\Lpa\DataModel\Common\Name;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testValidation()
    {
        $user = FixturesData::getUser();
        /* @var $name \Opg\Lpa\DataModel\Common\Name */
        $name = $user->get('name');

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
        $user = FixturesData::getUser();
        $name = $user->get('name');

        $this->assertEquals('Mr Chris Smith', '' . $name);
    }
}
