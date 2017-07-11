<?php

namespace OpgTest\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Common\EmailAddress;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;

class EmailAddressTest extends \PHPUnit_Framework_TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $email \Opg\Lpa\DataModel\Common\EmailAddress */
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
}
