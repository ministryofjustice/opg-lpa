<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
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

        $lpaArray = $lpa->toArray();

        $this->assertEquals($lpa->get('id'), $lpaArray['id']);
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

    public function testCanGenerateLP1()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->canGenerateLP1());
    }

    public function testCanGenerateLP3()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->canGenerateLP3());
    }

    public function testCanGenerateLPA120()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('reducedFeeUniversalCredit', true);
        $this->assertTrue($lpa->canGenerateLPA120());
    }

    public function testCanGenerateLPA120NotCompleted()
    {
        $lpa = new Lpa();
        $this->assertFalse($lpa->canGenerateLPA120());
    }

    public function testCanGenerateLPA120NoPayment()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('payment', null);
        $this->assertFalse($lpa->canGenerateLPA120());
    }

    public function testIsStateStartedBlankLpa()
    {
        $lpa = new Lpa();
        $this->assertFalse($lpa->isStateStarted());
    }

    public function testIsStateCreatedBlankLpa()
    {
        $lpa = new Lpa();
        $this->assertFalse($lpa->isStateCreated());
    }

    public function testIsStateCompletedBlankLpa()
    {
        $lpa = new Lpa();
        $this->assertFalse($lpa->isStateCompleted());
    }

    public function testPaymentResolvedCard()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('method', 'card');
        $lpa->get('payment')->set('reference', 'testreference');
        $this->assertTrue($lpa->isPaymentResolved());
    }

    public function testPaymentResolvedUnknownPaymentMethod()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('method', 'unknown');
        $this->assertFalse($lpa->isPaymentResolved());
    }

    public function testIsEligibleForFeeReductionNoPayment()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('payment', null);
        $this->assertFalse($lpa->isEligibleForFeeReduction());
    }

    public function testIsWhoAreYouAnswered()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->isWhoAreYouAnswered());
    }

    public function testLpaHasCorrespondent()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasCorrespondent());
    }

    public function testLpaHasApplicant()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasApplicant());
    }

    public function testLpaHasFinishedCreation()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasFinishedCreation());
    }

    public function testLpaHasFinishedCreationReplacementAttorneyStepInWhenLastPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $this->assertTrue($lpa->hasFinishedCreation());
    }

    public function testLpaHasFinishedCreationPrimaryAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $this->assertTrue($lpa->hasFinishedCreation());
    }

    public function testLpaHasFinishedCreationHasMultipleReplacementAttorneys()
    {
        $lpa = FixturesData::getPfLpa();
        //Set only one primary attorney
        $lpa->get('document')->set('primaryAttorneys', [$lpa->get('document')->get('primaryAttorneys')[0]]);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $this->assertTrue($lpa->hasFinishedCreation());
    }

    public function testLpaHasCreated()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasCreated());
    }

    public function testLpaHasPeopleToNotify()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasPeopleToNotify());
    }

    public function testLpaHasPeopleToNotifyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasPeopleToNotify(0));
    }

    public function testLpaReplacementAttorneysMakeDecisionJointlyAndSeverally()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $this->assertTrue($lpa->isHowReplacementAttorneysMakeDecisionJointlyAndSeverally());
    }

    public function testLpaReplacementAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        $this->assertTrue($lpa->isHowReplacementAttorneysMakeDecisionJointly());
    }

    public function testLpaReplacementAttorneysMakeDecisionDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
        $this->assertTrue($lpa->isHowReplacementAttorneysMakeDecisionDepends());
    }

    public function testIsReplacementAttorneyStepInDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS);
        $this->assertTrue($lpa->isWhenReplacementAttorneyStepInDepends());
    }

    public function testIsReplacementAttorneyStepInWhenLastPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
        $this->assertTrue($lpa->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct());
    }

    public function testIsReplacementAttorneyStepInWhenFirstPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);
        $this->assertTrue($lpa->isWhenReplacementAttorneyStepInWhenFirstPrimaryUnableAct());
    }

    public function testHasReplacementAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasReplacementAttorney());
    }

    public function testHasReplacementAttorneyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasReplacementAttorney(0));
    }

    public function testIsPrimaryAttorneysMakeDecisionJointlyAndSeverally()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $this->assertTrue($lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally());
    }

    public function testIsPrimaryAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        $this->assertTrue($lpa->isHowPrimaryAttorneysMakeDecisionJointly());
    }

    public function testIsPrimaryAttorneysMakeDecisionDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
        $this->assertTrue($lpa->isHowPrimaryAttorneysMakeDecisionDepends());
    }

    public function testHasPrimaryAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasPrimaryAttorney());
    }

    public function testLpaHasPrimaryAttorneyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $this->assertTrue($lpa->hasPrimaryAttorney(0));
    }

    public function testHasTrustCorporation()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('primaryAttorneys', [new TrustCorporation()]);
        $this->assertTrue($lpa->hasTrustCorporation());

        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('replacementAttorneys', [new TrustCorporation()]);
        $this->assertTrue($lpa->hasTrustCorporation());

        $lpa = FixturesData::getHwLpa();
        $this->assertFalse($lpa->hasTrustCorporation());
    }

    public function testHasTrustCorporationPrimary()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('primaryAttorneys', [new TrustCorporation()]);
        $this->assertTrue($lpa->hasTrustCorporation('primary'));
        $this->assertFalse($lpa->hasTrustCorporation('replacement'));
    }

    public function testHasTrustCorporationReplacement()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('replacementAttorneys', [new TrustCorporation()]);
        $this->assertFalse($lpa->hasTrustCorporation('primary'));
        $this->assertTrue($lpa->hasTrustCorporation('replacement'));
    }
}
