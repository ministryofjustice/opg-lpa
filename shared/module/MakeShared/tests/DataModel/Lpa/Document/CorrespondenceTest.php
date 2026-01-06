<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeSharedTest\DataModel\FixturesData;
use MakeSharedTest\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CorrespondenceTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Correspondence::class);

        Correspondence::loadValidatorMetadata($metadata);

        $this->assertEquals(8, count($metadata->getConstrainedProperties()));
        $this->assertContains('who', $metadata->getConstrainedProperties());
        $this->assertContains('name', $metadata->getConstrainedProperties());
        $this->assertContains('company', $metadata->getConstrainedProperties());
        $this->assertContains('address', $metadata->getConstrainedProperties());
        $this->assertContains('email', $metadata->getConstrainedProperties());
        $this->assertContains('phone', $metadata->getConstrainedProperties());
        $this->assertContains('contactByPost', $metadata->getConstrainedProperties());
        $this->assertContains('contactInWelsh', $metadata->getConstrainedProperties());
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
