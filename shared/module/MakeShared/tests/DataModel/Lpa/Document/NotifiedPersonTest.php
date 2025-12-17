<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class NotifiedPersonTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(NotifiedPerson::class);

        NotifiedPerson::loadValidatorMetadata($metadata);

        $this->assertEquals(3, count($metadata->getConstrainedProperties()));

        $this->assertContains('id', $metadata->getConstrainedProperties());
        $this->assertContains('name', $metadata->getConstrainedProperties());
        $this->assertContains('address', $metadata->getConstrainedProperties());
    }

    public function testMap()
    {
        $notifiedPerson = FixturesData::getNotifiedPerson();

        $this->assertEquals(1, $notifiedPerson->get('id'));

        $this->assertEquals('Miss', $notifiedPerson->get('name')->title);
        $this->assertEquals('Elizabeth', $notifiedPerson->get('name')->first);
        $this->assertEquals('Stout', $notifiedPerson->get('name')->last);

        $this->assertEquals('747 Station Road', $notifiedPerson->get('address')->address1);
        $this->assertEquals('Clayton le Moors', $notifiedPerson->get('address')->address2);
        $this->assertEquals('Lancashire, England', $notifiedPerson->get('address')->address3);
        $this->assertEquals('WN8A 8AQ', $notifiedPerson->get('address')->postcode);
    }

    public function testValidation()
    {
        $notifiedPerson = FixturesData::getNotifiedPerson();

        $validatorResponse = $notifiedPerson->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $notifiedPerson = new NotifiedPerson();

        $validatorResponse = $notifiedPerson->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(2, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['name']);
        $this->assertNotNull($errors['address']);
    }

    public function testGetsAndSets()
    {
        $model = new NotifiedPerson();

        $name = new Name();
        $address = new Address();

        $model->setId(123)
            ->setName($name)
            ->setAddress($address);

        $this->assertEquals(123, $model->getId());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($address, $model->getAddress());
    }
}
