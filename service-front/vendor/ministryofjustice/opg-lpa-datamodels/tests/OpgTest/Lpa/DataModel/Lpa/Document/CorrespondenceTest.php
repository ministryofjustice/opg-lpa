<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
use Opg\Lpa\DataModel\Common\PhoneNumber;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CorrespondenceTest extends TestCase
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

    public function testGetsAndSets()
    {
        $model = new Correspondence();

        $name = new LongName();
        $phone = new PhoneNumber();
        $address = new Address();
        $email = new EmailAddress();

        $model->setWho(Correspondence::WHO_CERTIFICATE_PROVIDER)
            ->setName($name)
            ->setCompany('Test Company')
            ->setAddress($address)
            ->setEmail($email)
            ->setPhone($phone)
            ->setContactByPost(true)
            ->setContactInWelsh(true)
            ->setContactDetailsEnteredManually(true);

        $this->assertEquals(Correspondence::WHO_CERTIFICATE_PROVIDER, $model->getWho());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals('Test Company', $model->getCompany());
        $this->assertEquals($address, $model->getAddress());
        $this->assertEquals($email, $model->getEmail());
        $this->assertEquals($phone, $model->getPhone());
        $this->assertEquals(true, $model->isContactByPost());
        $this->assertEquals(true, $model->isContactInWelsh());
        $this->assertEquals(true, $model->isContactDetailsEnteredManually());
    }
}
