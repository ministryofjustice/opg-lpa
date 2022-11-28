<?php

namespace MakeSharedTest\DataModel\Common;

use MakeShared\DataModel\Common\Name;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $name \MakeShared\DataModel\Common\Name */
        $name = $donor->get('name');

        $validatorResponse = $name->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $name = new Name();
        $name->set('title', FixturesData::generateRandomString(Name::TITLE_MAX_LENGTH + 1));
        $name->set('first', FixturesData::generateRandomString(Name::FIRST_NAME_MAX_LENGTH + 1));
        $name->set('last', FixturesData::generateRandomString(Name::LAST_NAME_MAX_LENGTH + 1));

        $validatorResponse = $name->validate();
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

        $name = new Name();
        $name->set('title', null);
        $name->set('first', FixturesData::generateRandomString(Name::FIRST_NAME_MAX_LENGTH));
        $name->set('last', FixturesData::generateRandomString(Name::LAST_NAME_MAX_LENGTH));

        $validatorResponse = $name->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationTitleCanNotBeEmpty()
    {
        // Title cannot be an empty string, it must have a value, or be null.

        $name = new Name();
        $name->set('title', '');
        $name->set('first', FixturesData::generateRandomString(Name::FIRST_NAME_MAX_LENGTH));
        $name->set('last', FixturesData::generateRandomString(Name::LAST_NAME_MAX_LENGTH));

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
        $name = $donor->get('name');

        $this->assertEquals('Hon Ayden Armstrong', '' . $name);
    }

    public function testGetsAndSets()
    {
        $model = new Name();

        $model->setTitle('Mr')
            ->setFirst('Unit')
            ->setLast('Test');

        $this->assertEquals('Mr', $model->getTitle());
        $this->assertEquals('Unit', $model->getFirst());
        $this->assertEquals('Test', $model->getLast());
    }
}
