<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DonorTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Donor::class);

        Donor::loadValidatorMetadata($metadata);

        $this->assertEquals(6, count($metadata->getConstrainedProperties()));
        $this->assertContains('name', $metadata->getConstrainedProperties());
        $this->assertContains('otherNames', $metadata->getConstrainedProperties());
        $this->assertContains('address', $metadata->getConstrainedProperties());
        $this->assertContains('dob', $metadata->getConstrainedProperties());
        $this->assertContains('email', $metadata->getConstrainedProperties());
        $this->assertContains('canSign', $metadata->getConstrainedProperties());
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

    public function testGetsAndSets()
    {
        $model = new Donor();

        $name = new LongName();
        $address = new Address();
        $dob = new Dob();
        $email = new EmailAddress();

        $model->setName($name)
            ->setOtherNames('Other Names')
            ->setAddress($address)
            ->setDob($dob)
            ->setEmail($email)
            ->setCanSign(true);

        $this->assertEquals($name, $model->getName());
        $this->assertEquals('Other Names', $model->getOtherNames());
        $this->assertEquals($address, $model->getAddress());
        $this->assertEquals($dob, $model->getDob());
        $this->assertEquals($email, $model->getEmail());
        $this->assertEquals(true, $model->isCanSign());
    }
}
