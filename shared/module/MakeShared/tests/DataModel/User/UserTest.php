<?php

namespace MakeSharedTest\DataModel\User;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use DateTime;

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
        $this->assertEquals(4, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['id']);
        $this->assertNotNull($errors['createdAt']);
        $this->assertNotNull($errors['updatedAt']);
    }

    public function testToArrayForMongo()
    {
        $user = FixturesData::getUser();

        $lpaArray = $user->toArray();

        $this->assertEquals($user->get('id'), $lpaArray['id']);
    }

    public function testGetsAndSets()
    {
        $model = new User();

        $now = new DateTime();
        $name = new Name();
        $address = new Address();
        $dob = new Dob();
        $email = new EmailAddress();

        $model->setId(123)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setName($name)
            ->setAddress($address)
            ->setDob($dob)
            ->setEmail($email);

        $this->assertEquals(123, $model->getId());
        $this->assertEquals($now, $model->getCreatedAt());
        $this->assertEquals($now, $model->getUpdatedAt());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($address, $model->getAddress());
        $this->assertEquals($dob, $model->getDob());
        $this->assertEquals($email, $model->getEmail());
    }
}
