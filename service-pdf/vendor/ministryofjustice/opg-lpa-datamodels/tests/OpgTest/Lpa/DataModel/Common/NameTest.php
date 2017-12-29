<?php

namespace OpgTest\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\Common\Name;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $name \Opg\Lpa\DataModel\Common\Name */
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
