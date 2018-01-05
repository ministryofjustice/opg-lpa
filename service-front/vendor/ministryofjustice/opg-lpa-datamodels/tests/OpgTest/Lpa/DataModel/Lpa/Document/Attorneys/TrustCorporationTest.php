<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TrustCorporationTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(TrustCorporation::class);

        TrustCorporation::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->properties));
        $this->assertNotNull($metadata->properties['name']);
        $this->assertNotNull($metadata->properties['number']);
    }

    public function testToArray()
    {
        $data = FixturesData::getAttorneyTrustJson();

        $attorney = AbstractAttorney::factory($data);
        $attorneyArray = $attorney->toArray();

        $this->assertEquals('trust', $attorneyArray['type']);
    }

    public function testValidation()
    {
        $trust = FixturesData::getAttorneyTrust();

        $validatorResponse = $trust->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $trustCorporation = new TrustCorporation();

        $validatorResponse = $trustCorporation->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['name']);
        $this->assertNotNull($errors['number']);
    }

    public function testGetsAndSets()
    {
        $model = new TrustCorporation();

        $address = new Address();
        $email = new EmailAddress();
        $name = new Name();

        $model->setId(123)
            ->setAddress($address)
            ->setEmail($email)
            ->setName($name)
            ->setNumber('123456');

        $this->assertEquals(123, $model->getId());
        $this->assertEquals($address, $model->getAddress());
        $this->assertEquals($email, $model->getEmail());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals('123456', $model->getNumber());
    }
}
