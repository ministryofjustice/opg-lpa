<?php

namespace MakeSharedTest\DataModel\Common;

use MakeShared\DataModel\Common\LongName;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class LongNameTest extends TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $longName \MakeShared\DataModel\Common\LongName */
        $longName = $donor->get('name');

        $validatorResponse = $longName->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $longName = new LongName();
        $longName->set('title', FixturesData::generateRandomString(LongName::TITLE_MAX_LENGTH + 1));
        $longName->set('first', FixturesData::generateRandomString(LongName::FIRST_NAME_MAX_LENGTH + 1));
        $longName->set('last', FixturesData::generateRandomString(LongName::LAST_NAME_MAX_LENGTH + 1));

        $validatorResponse = $longName->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['title']);
        $this->assertNotNull($errors['first']);
        $this->assertNotNull($errors['last']);
    }

    public function testValidationTitleCanBeNull()
    {
        // Title can now be null, indicating the user choose not to specify.

        $name = new LongName();
        $name->set('title', null);
        $name->set('first', FixturesData::generateRandomString(LongName::FIRST_NAME_MAX_LENGTH));
        $name->set('last', FixturesData::generateRandomString(LongName::LAST_NAME_MAX_LENGTH));

        $validatorResponse = $name->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationTitleCanNotBeEmpty()
    {
        // Title cannot be an empty string, it must have a value, or be null.

        $name = new LongName();
        $name->set('title', '');
        $name->set('first', FixturesData::generateRandomString(LongName::FIRST_NAME_MAX_LENGTH));
        $name->set('last', FixturesData::generateRandomString(LongName::LAST_NAME_MAX_LENGTH));

        $validatorResponse = $name->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['title']);
    }

    public function testToString()
    {
        $donor = FixturesData::getDonor();
        $longName = $donor->get('name');

        $this->assertEquals('Hon Ayden Armstrong', '' . $longName);
    }

    public function testGetsAndSets()
    {
        $model = new LongName();

        $model->setTitle('Mr')
            ->setFirst('Unit')
            ->setLast('Test');

        $this->assertEquals('Mr', $model->getTitle());
        $this->assertEquals('Unit', $model->getFirst());
        $this->assertEquals('Test', $model->getLast());
    }
}
