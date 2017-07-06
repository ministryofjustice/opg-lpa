<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class HumanTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Human::class);

        Human::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->properties));
        $this->assertNotNull($metadata->properties['name']);
        $this->assertNotNull($metadata->properties['dob']);
    }

    public function testMap()
    {
        $data = FixturesData::getAttorneyHumanJson();

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals('Dr', $attorney->get('name')->title);
        $this->assertEquals('Wellington', $attorney->get('name')->first);
        $this->assertEquals('Gastri', $attorney->get('name')->last);

        $this->assertEquals(new \DateTime('1982-09-02T00:00:00.000000+0000'), $attorney->get('dob')->date);
    }

    public function testToArray()
    {
        $data = FixturesData::getAttorneyHumanJson();

        $attorney = AbstractAttorney::factory($data);
        $attorneyArray = $attorney->toArray();

        $this->assertEquals('human', $attorneyArray['type']);
    }

    public function testValidation()
    {
        $human = FixturesData::getAttorneyHuman();

        $validatorResponse = $human->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $human = new Human();

        $validatorResponse = $human->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['address']);
        $this->assertNotNull($errors['name']);
        $this->assertNotNull($errors['dob']);
    }
}
