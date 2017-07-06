<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TrustCorporationTest extends \PHPUnit_Framework_TestCase
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
}
