<?php

namespace OpgTest\Lpa\DataModel\User;

use Opg\Lpa\DataModel\Common\EmailAddress;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testValidation()
    {
        $user = FixturesData::getUser();
        /* @var $email \Opg\Lpa\DataModel\Common\EmailAddress */
        $email = $user->get('email');

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
        $user = FixturesData::getUser();
        $email = $user->get('email');

        $this->assertEquals('opgcasper+1498828259628334011473@gmail.com', '' . $email);
    }
}
