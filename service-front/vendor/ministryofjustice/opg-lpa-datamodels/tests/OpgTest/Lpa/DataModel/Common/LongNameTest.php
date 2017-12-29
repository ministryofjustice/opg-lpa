<?php

namespace OpgTest\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\Common\LongName;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;

class LongNameTest extends TestCase
{
    public function testValidation()
    {
        $donor = FixturesData::getDonor();
        /* @var $longName \Opg\Lpa\DataModel\Common\LongName */
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
