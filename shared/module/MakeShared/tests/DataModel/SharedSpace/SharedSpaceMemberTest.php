<?php

namespace MakeSharedTest\DataModel\SharedSpace;

use DateTime;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class SharedSpaceMemberTest extends TestCase
{
    public function testValidation()
    {
        $member = FixturesData::getSharedSpaceMember();

        $validatorResponse = $member->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $member = new SharedSpaceMember();

        $validatorResponse = $member->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['sharedSpaceId']);
        $this->assertNotNull($errors['userId']);
        $this->assertNotNull($errors['createdAt']);
    }

    public function testDefaultsToNotAdminAndActive()
    {
        $member = new SharedSpaceMember();

        $this->assertFalse($member->getIsAdmin());
        $this->assertTrue($member->getIsActive());
    }

    public function testToArrayForMongo()
    {
        $member = FixturesData::getSharedSpaceMember();

        $memberArray = $member->toArray();

        $this->assertEquals($member->getSharedSpaceId(), $memberArray['sharedSpaceId']);
        $this->assertEquals($member->getUserId(), $memberArray['userId']);
    }

    public function testGetsAndSets()
    {
        $model = new SharedSpaceMember();

        $now = new DateTime();

        $model->setSharedSpaceId('e551d8b14c408f7efb7358fb258f1b12')
            ->setUserId('f13d97fca1cd06fd7d3e10e5c7bfa123')
            ->setIsAdmin(true)
            ->setIsActive(false)
            ->setCreatedAt($now);

        $this->assertEquals('e551d8b14c408f7efb7358fb258f1b12', $model->getSharedSpaceId());
        $this->assertEquals('f13d97fca1cd06fd7d3e10e5c7bfa123', $model->getUserId());
        $this->assertTrue($model->getIsAdmin());
        $this->assertFalse($model->getIsActive());
        $this->assertEquals($now, $model->getCreatedAt());
    }
}
