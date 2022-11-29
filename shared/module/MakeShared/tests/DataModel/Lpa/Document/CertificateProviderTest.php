<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CertificateProviderTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(CertificateProvider::class);

        CertificateProvider::loadValidatorMetadata($metadata);

        $this->assertEquals(2, count($metadata->properties));
        $this->assertNotNull($metadata->properties['name']);
        $this->assertNotNull($metadata->properties['address']);
    }

    public function testMap()
    {
        $certificateProvider = FixturesData::getCertificateProvider();

        $this->assertEquals('Mr', $certificateProvider->get('name')->title);
        $this->assertEquals('Certy', $certificateProvider->get('name')->first);
        $this->assertEquals('Edwards', $certificateProvider->get('name')->last);

        $this->assertEquals('Sixthaven', $certificateProvider->get('address')->address1);
        $this->assertEquals('Little Gorway', $certificateProvider->get('address')->address2);
        $this->assertEquals('Walsall', $certificateProvider->get('address')->address3);
        $this->assertEquals('WS1 3BQ', $certificateProvider->get('address')->postcode);

        $testable = new TestableCertificateProvider();
        $this->assertEquals('testValue', $testable->testMap('testProperty', 'testValue'));
    }

    public function testValidation()
    {
        $certificateProvider = FixturesData::getCertificateProvider();

        $validatorResponse = $certificateProvider->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $certificateProvider = new CertificateProvider();

        $validatorResponse = $certificateProvider->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(2, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['name']);
        $this->assertNotNull($errors['address']);
    }

    public function testGetsAndSets()
    {
        $model = new CertificateProvider();

        $name = new Name();
        $address = new Address();

        $model->setName($name)
            ->setAddress($address);

        $this->assertEquals($name, $model->getName());
        $this->assertEquals($address, $model->getAddress());
    }
}
