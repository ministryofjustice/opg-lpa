<?php

namespace OpgTest\Lpa\DataModel\User;

use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testValidation()
    {
        $user = FixturesData::getUser();

        $validatorResponse = $user->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $name = new User();

        $validatorResponse = $name->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['id']);
        $this->assertNotNull($errors['createdAt']);
        $this->assertNotNull($errors['updatedAt']);
    }

    public function testToMongoArray()
    {
        $user = FixturesData::getUser();

        $mongoArray = $user->toMongoArray();
        $this->assertEquals($user->get('id'), $mongoArray['_id']);
    }
}
