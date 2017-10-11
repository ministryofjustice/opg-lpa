<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\Document\Donor;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DonorTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Donor::class);

        Donor::loadValidatorMetadata($metadata);

        $this->assertEquals(6, count($metadata->properties));
        $this->assertNotNull($metadata->properties['name']);
        $this->assertNotNull($metadata->properties['otherNames']);
        $this->assertNotNull($metadata->properties['address']);
        $this->assertNotNull($metadata->properties['dob']);
        $this->assertNotNull($metadata->properties['email']);
        $this->assertNotNull($metadata->properties['canSign']);
    }

    public function testMap()
    {
        $donor = FixturesData::getDonor();

        $this->assertEquals('Hon', $donor->get('name')->title);
        $this->assertEquals('Ayden', $donor->get('name')->first);
        $this->assertEquals('Armstrong', $donor->get('name')->last);

        $this->assertEquals('562 Queen Street', $donor->get('address')->address1);
        $this->assertEquals('Charlestown', $donor->get('address')->address2);
        $this->assertEquals('Cornwall, England', $donor->get('address')->address3);
        $this->assertEquals('EH9K 8UC', $donor->get('address')->postcode);

        $this->assertEquals('92zx2n1nk@wx.co.uk', $donor->get('email')->address);
    }

    public function testValidation()
    {
        $donor = FixturesData::getDonor();

        $validatorResponse = $donor->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $donor = new Donor();

        $validatorResponse = $donor->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(4, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['name']);
        $this->assertNotNull($errors['address']);
        $this->assertNotNull($errors['dob']);
        $this->assertNotNull($errors['canSign']);
    }
}
