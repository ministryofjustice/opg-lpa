<?php

namespace MakeSharedTest\DataModel\Lpa\Document\Attorneys;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class HumanTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Human::class);

        Human::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->getConstrainedProperties()));
        $this->assertContains('name', $metadata->getConstrainedProperties());
        $this->assertContains('dob', $metadata->getConstrainedProperties());
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

    public function testGetsAndSets()
    {
        $model = new Human();

        $address = new Address();
        $email = new EmailAddress();
        $name = new Name();
        $dob = new Dob();

        $model->setId(123)
            ->setAddress($address)
            ->setEmail($email)
            ->setName($name)
            ->setDob($dob);

        $this->assertEquals(123, $model->getId());
        $this->assertEquals($address, $model->getAddress());
        $this->assertEquals($email, $model->getEmail());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($dob, $model->getDob());
    }
}
