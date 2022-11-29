<?php

namespace MakeSharedTest\DataModel\Common;

use MakeShared\DataModel\Common\EmailAddress;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $email \MakeShared\DataModel\Common\EmailAddress */
        $email = $donor->get('email');

        $validatorResponse = $email->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $email = new EmailAddress();

        $validatorResponse = $email->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['address']);
    }

    public function testToString()
    {
        $donor = FixturesData::getDonor();
        $email = $donor->get('email');

        $this->assertEquals('92zx2n1nk@wx.co.uk', '' . $email);
    }

    public function testGetsAndSets()
    {
        $model = new EmailAddress();

        $model->setAddress('test@test.com');

        $this->assertEquals('test@test.com', $model->getAddress());
    }
}
