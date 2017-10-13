<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DocumentTest extends TestCase
{
    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(Document::class);

        Document::loadValidatorMetadata($metadata);

        $this->assertEquals(12, count($metadata->properties));
        $this->assertNotNull($metadata->properties['type']);
        $this->assertNotNull($metadata->properties['donor']);
        $this->assertNotNull($metadata->properties['whoIsRegistering']);
        $this->assertNotNull($metadata->properties['primaryAttorneyDecisions']);
        $this->assertNotNull($metadata->properties['replacementAttorneyDecisions']);
        $this->assertNotNull($metadata->properties['correspondent']);
        $this->assertNotNull($metadata->properties['instruction']);
        $this->assertNotNull($metadata->properties['preference']);
        $this->assertNotNull($metadata->properties['certificateProvider']);
        $this->assertNotNull($metadata->properties['primaryAttorneys']);
        $this->assertNotNull($metadata->properties['replacementAttorneys']);
        $this->assertNotNull($metadata->properties['peopleToNotify']);
    }

    public function testMap()
    {
        $document = FixturesData::getHwLpa()->get('document');

        $this->assertNotNull($document->get('donor'));
        $this->assertNotNull($document->get('primaryAttorneyDecisions'));
        $this->assertNotNull($document->get('replacementAttorneyDecisions'));
        $this->assertNotNull($document->get('correspondent'));
        $this->assertNotNull($document->get('certificateProvider'));
        $this->assertNotNull($document->get('primaryAttorneys'));
        $this->assertNotNull($document->get('replacementAttorneys'));
        $this->assertNotNull($document->get('peopleToNotify'));
    }

    public function testMapAbstractAttorney()
    {
        $document = new TestableDocument();
        $replacementAttorneys = [new Human()];

        $mapped = $document->testMap('replacementAttorneys', $replacementAttorneys);
        $this->assertTrue($replacementAttorneys === $mapped);
    }

    public function testValidation()
    {
        $document = FixturesData::getPfLpaDocument();

        $validatorResponse = $document->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $toLong = FixturesData::generateRandomString(10001);

        $document = new Document();
        $document->set('type', 'incorrect');
        $document->set('instruction', $toLong);
        $document->set('preference', $toLong);

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['type']);
        $this->assertNotNull($errors['instruction']);
        $this->assertNotNull($errors['preference']);
    }

    public function testValidationTypesFailed()
    {
        $document = new Document();
        $document->set('whoIsRegistering', []);
        $document->set('instruction', new \DateTime());
        $document->set('preference', new \DateTime());

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(3, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['whoIsRegistering']);
        $this->assertNotNull($errors['instruction']);
        $this->assertNotNull($errors['preference']);
    }

    public function testValidationTrustAttorneysFailed()
    {
        $document = new Document();

        $document->set('primaryAttorneys', [FixturesData::getAttorneyTrust(1), FixturesData::getAttorneyTrust(2)]);

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['primaryAttorneys/replacementAttorneys']);
    }

    public function testValidationWhoIsRegistering()
    {
        $document = FixturesData::getPfLpaDocument();
        $document->set('whoIsRegistering', [1]);

        $validatorResponse = $document->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationWhoIsRegisteringFailed()
    {
        $document = FixturesData::getPfLpaDocument();
        $document->set('whoIsRegistering', [-1]);

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['whoIsRegistering']);
    }

    public function testValidationMaxPeopleToNotifyFailed()
    {
        $document = FixturesData::getPfLpaDocument();
        $document->set('peopleToNotify', [
            new NotifiedPerson(),
            new NotifiedPerson(),
            new NotifiedPerson(),
            new NotifiedPerson(),
            new NotifiedPerson(),
            new NotifiedPerson()
        ]);

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        //$this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['peopleToNotify']);
    }

    public function testValidationPrimaryAttorneysDuplicateIdFailed()
    {
        $document = FixturesData::getPfLpaDocument();
        FixturesData::getPrimaryAttorneys($document)[1]->set('id', 1);

        $validatorResponse = $document->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(1, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['primaryAttorneys']);
    }

    public function testGetPrimaryAttorneyById()
    {
        $document = new Document();

        $attorney = $document->getPrimaryAttorneyById(2);
        $this->assertNull($attorney);

        $document = FixturesData::getHwLpa()->get('document');

        $attorney = $document->getPrimaryAttorneyById(2);
        $this->assertNotNull($attorney);

        $attorney = $document->getPrimaryAttorneyById(-1);
        $this->assertNull($attorney);
    }

    public function testGetReplacementAttorneyById()
    {
        $document = new Document();

        $attorney = $document->getReplacementAttorneyById(3);
        $this->assertNull($attorney);

        $document = FixturesData::getPfLpa()->get('document');

        $attorney = $document->getReplacementAttorneyById(3);
        $this->assertNotNull($attorney);

        $attorney = $document->getReplacementAttorneyById(-1);
        $this->assertNull($attorney);
    }
}
