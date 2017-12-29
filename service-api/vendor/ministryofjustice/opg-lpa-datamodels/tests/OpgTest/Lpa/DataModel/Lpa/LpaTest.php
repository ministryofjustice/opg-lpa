<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use OpgTest\Lpa\DataModel\FixturesData;
use OpgTest\Lpa\DataModel\TestHelper;
use PHPUnit\Framework\TestCase;
use DateTime;

class LpaTest extends TestCase
{
    public function testValidation()
    {
        $lpa = FixturesData::getPfLpa();
        $validatorResponse = $lpa->validate();
        $this->assertFalse($validatorResponse->hasErrors());
    }

    public function testValidationFailed()
    {
        $lpa = new Lpa();
        //This causes an exception in the validation routines when formatting the error message
        $lpa->get('metadata')['test'] = FixturesData::generateRandomString(1048566);

        $validatorResponse = $lpa->validate();
        $this->assertTrue($validatorResponse->hasErrors());
        $errors = $validatorResponse->getArrayCopy();
        $this->assertEquals(7, count($errors));
        TestHelper::assertNoDuplicateErrorMessages($errors, $this);
        $this->assertNotNull($errors['id']);
        $this->assertNotNull($errors['startedAt']);
        $this->assertNotNull($errors['updatedAt']);
        $this->assertNotNull($errors['user']);
        $this->assertNotNull($errors['whoAreYouAnswered']);
        $this->assertNotNull($errors['locked']);
        $this->assertNotNull($errors['metadata']);
    }

    public function testToArrayForMongo()
    {
        $lpa = FixturesData::getHwLpa();

        $lpaArray = $lpa->toArray(function (DateTime $dateTime) {
            //  Dummy callable
        });

        $this->assertEquals($lpa->get('id'), $lpaArray['_id']);
    }

    public function testAbbreviatedToArray()
    {
        $lpa = FixturesData::getHwLpa();

        $abbreviatedToArray = $lpa->abbreviatedToArray();
        $this->assertEquals(10, count($abbreviatedToArray));
        $this->assertEquals(2, count($abbreviatedToArray['document']));
        $this->assertEquals(4, count($abbreviatedToArray['metadata']));
    }

    public function testLpaIsEqual()
    {
        $lpa = FixturesData::getPfLpa();
        $comparisonLpa = FixturesData::getPfLpa();

        //Reference should be different
        $this->assertFalse($lpa === $comparisonLpa);
        //But the object should be structurally the same
        /** @noinspection PhpNonStrictObjectEqualityInspection */
        $this->assertTrue($lpa == $comparisonLpa);
        $this->assertEquals($lpa, $comparisonLpa);
        $this->assertTrue($lpa->equals($comparisonLpa));
    }

    public function testLpaIsNotEqual()
    {
        $lpa = FixturesData::getPfLpa();
        $comparisonLpa = FixturesData::getPfLpa();

        $comparisonLpa->get('document')->donor->name->first = "Edited";

        //Verify edits have been applied
        $this->assertEquals("Ayden", $lpa->get('document')->donor->name->first);
        $this->assertEquals("Edited", $comparisonLpa->get('document')->donor->name->first);

        /** @noinspection PhpNonStrictObjectEqualityInspection */
        $this->assertFalse($lpa == $comparisonLpa);
        $this->assertNotEquals($lpa, $comparisonLpa);
        $this->assertFalse($lpa->equals($comparisonLpa));
    }

    public function testLpaIsNotEqualMetadata()
    {
        $lpa = FixturesData::getPfLpa();
        $comparisonLpa = FixturesData::getPfLpa();

        $comparisonLpa->get('metadata')['analyticsReturnCount']++;

        //Verify edits have been applied
        $this->assertEquals(4, $lpa->get('metadata')['analyticsReturnCount']);
        $this->assertEquals(5, $comparisonLpa->get('metadata')['analyticsReturnCount']);

        /** @noinspection PhpNonStrictObjectEqualityInspection */
        $this->assertFalse($lpa == $comparisonLpa);
        $this->assertNotEquals($lpa, $comparisonLpa);
        $this->assertFalse($lpa->equals($comparisonLpa));
    }

    public function testLpaIsEqualIgnoringMetadata()
    {
        $lpa = FixturesData::getPfLpa();
        $comparisonLpa = FixturesData::getPfLpa();

        $comparisonLpa->get('metadata')['analyticsReturnCount']++;

        $this->assertTrue($lpa->get('document') == $comparisonLpa->get('document'));
        $this->assertEquals($lpa->get('document'), $comparisonLpa->get('document'));
        $this->assertTrue($lpa->equalsIgnoreMetadata($comparisonLpa));
    }

    public function testGetsAndSets()
    {
        $model = new Lpa();

        $now = new DateTime();
        $payment = new Payment();
        $document = new Document();
        $metadata = [];

        $model->setId(12345)
            ->setStartedAt($now)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setCompletedAt($now)
            ->setLockedAt($now)
            ->setUser('123abc')
            ->setPayment($payment)
            ->setWhoAreYouAnswered(true)
            ->setLocked(true)
            ->setSeed(54321)
            ->setRepeatCaseNumber(11111)
            ->setDocument($document)
            ->setMetadata($metadata);

        $this->assertEquals(12345, $model->getId());
        $this->assertEquals($now, $model->getStartedAt());
        $this->assertEquals($now, $model->getCreatedAt());
        $this->assertEquals($now, $model->getUpdatedAt());
        $this->assertEquals($now, $model->getCompletedAt());
        $this->assertEquals($now, $model->getLockedAt());
        $this->assertEquals('123abc', $model->getUser());
        $this->assertEquals($payment, $model->getPayment());
        $this->assertEquals(true, $model->isWhoAreYouAnswered());
        $this->assertEquals(true, $model->isLocked());
        $this->assertEquals(54321, $model->getSeed());
        $this->assertEquals(11111, $model->getRepeatCaseNumber());
        $this->assertEquals($document, $model->getDocument());
        $this->assertEquals($metadata, $model->getMetadata());
    }
}
