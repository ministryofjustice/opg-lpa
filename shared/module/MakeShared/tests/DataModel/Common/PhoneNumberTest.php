<?php

namespace MakeSharedTest\DataModel\Common;

use MakeShared\DataModel\Common\PhoneNumber;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function testValidation()
    {
        $phone = new PhoneNumber();
        $phone->set('number', '02114214153553');

        $validatorResponse = $phone->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $phone = new PhoneNumber();

        $validatorResponse = $phone->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['number']);
    }

    public function testGetsAndSets()
    {
        $model = new PhoneNumber();

        $model->setNumber('0123456789');

        $this->assertEquals('0123456789', $model->getNumber());
    }
}
