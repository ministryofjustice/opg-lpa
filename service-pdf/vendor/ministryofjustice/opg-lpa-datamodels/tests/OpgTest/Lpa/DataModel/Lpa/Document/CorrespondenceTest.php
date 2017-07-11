<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CorrespondenceTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Correspondence::class);

        Correspondence::loadValidatorMetadata($metadata);

        $this->assertEquals(8, count($metadata->properties));
        $this->assertNotNull($metadata->properties['who']);
        $this->assertNotNull($metadata->properties['name']);
        $this->assertNotNull($metadata->properties['company']);
        $this->assertNotNull($metadata->properties['address']);
        $this->assertNotNull($metadata->properties['email']);
        $this->assertNotNull($metadata->properties['phone']);
        $this->assertNotNull($metadata->properties['contactByPost']);
        $this->assertNotNull($metadata->properties['contactInWelsh']);
    }

    public function testMap()
    {
        $correspondence = FixturesData::getCorrespondence();

        $this->assertEquals('Hon', $correspondence->get('name')->title);
        $this->assertEquals('Ayden', $correspondence->get('name')->first);
        $this->assertEquals('Armstrong', $correspondence->get('name')->last);

        $this->assertEquals('562 Queen Street', $correspondence->get('address')->address1);
        $this->assertEquals('Charlestown', $correspondence->get('address')->address2);
        $this->assertEquals('Cornwall, England', $correspondence->get('address')->address3);
        $this->assertEquals('EH9K 8UC', $correspondence->get('address')->postcode);

        $this->assertEquals('92zx2n1nk@wx.co.uk', $correspondence->get('email')->address);

        $this->assertEquals('012412141535', $correspondence->get('phone')->number);
    }

    public function testValidation()
    {
        $correspondence = FixturesData::getCorrespondence();

        $validatorResponse = $correspondence->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $correspondence = new Correspondence();

        $validatorResponse = $correspondence->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['name/company']);
        $this->assertNotNull($errors['who']);
        $this->assertNotNull($errors['address']);
    }
}
