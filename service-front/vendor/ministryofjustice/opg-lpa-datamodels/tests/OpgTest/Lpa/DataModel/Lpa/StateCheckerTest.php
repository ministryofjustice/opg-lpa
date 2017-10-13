<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use InvalidArgumentException;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use OpgTest\Lpa\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class StateCheckerTest extends TestCase
{
    public function testConstructor()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($lpa === $stateChecker->getLpa());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No LPA has been set
     */
    public function testConstructorNoLpa()
    {
        $stateChecker = new StateChecker(null);
        $stateChecker->getLpa();
    }

    public function testCanGenerateLP1()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->canGenerateLP1());
    }

    public function testCanGenerateLP3()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->canGenerateLP3());
    }

    public function testCanGenerateLPA120()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('reducedFeeUniversalCredit', true);
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->canGenerateLPA120());
    }

    public function testCanGenerateLPA120NotCompleted()
    {
        $lpa = new Lpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->canGenerateLPA120());
    }

    public function testCanGenerateLPA120NoPayment()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('payment', null);
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->canGenerateLPA120());
    }

    public function testIsStateStartedBlankLpa()
    {
        $lpa = new Lpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->isStateStarted());
    }

    public function testIsStateCreatedBlankLpa()
    {
        $lpa = new Lpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->isStateCreated());
    }

    public function testIsStateCompletedBlankLpa()
    {
        $lpa = new Lpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->isStateCompleted());
    }

    public function testPaymentResolvedCard()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('method', 'card');
        $lpa->get('payment')->set('reference', 'testreference');
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testPaymentResolved());
    }

    public function testPaymentResolvedUnknownPaymentMethod()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('payment')->set('method', 'unknown');
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertFalse($stateChecker->testPaymentResolved());
    }

    public function testIsEligibleForFeeReductionNoPayment()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('payment', null);
        $stateChecker = new StateChecker($lpa);
        $this->assertFalse($stateChecker->isEligibleForFeeReduction());
    }

    public function testIsWhoAreYouAnswered()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testIsWhoAreYouAnswered());
    }

    public function testLpaHasCorrespondent()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasCorrespondent());
    }

    public function testLpaHasApplicant()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasApplicant());
    }

    public function testLpaHasFinishedCreation()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasFinishedCreation());
    }

    public function testLpaHasFinishedCreationReplacementAttorneyStepInWhenLastPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasFinishedCreation());
    }

    public function testLpaHasFinishedCreationPrimaryAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasFinishedCreation());
    }

    public function testLpaHasFinishedCreationHasMultipleReplacementAttorneys()
    {
        $lpa = FixturesData::getPfLpa();
        //Set only one primary attorney
        $lpa->get('document')->set('primaryAttorneys', [$lpa->get('document')->get('primaryAttorneys')[0]]);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasFinishedCreation());
    }

    public function testLpaHasCreated()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasCreated());
    }

    public function testLpaHasPeopleToNotify()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasPeopleToNotify());
    }

    public function testLpaHasPeopleToNotifyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasPeopleToNotify(0));
    }

    public function testLpaReplacementAttorneysMakeDecisionJointlyAndSeverally()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaReplacementAttorneysMakeDecisionJointlyAndSeverally());
    }

    public function testLpaReplacementAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaReplacementAttorneysMakeDecisionJointly());
    }

    public function testLpaReplacementAttorneysMakeDecisionDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getReplacementAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaReplacementAttorneysMakeDecisionDepends());
    }

    public function testLpaReplacementAttorneyStepInDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->lpaReplacementAttorneyStepInDepends());
    }

    public function testLpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct());
    }

    public function testLpaReplacementAttorneyStepInWhenFirstPrimaryUnableAct()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        FixturesData::getReplacementAttorneyDecisions($lpa)
            ->set('when', ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->lpaReplacementAttorneyStepInWhenFirstPrimaryUnableAct());
    }

    public function testLpaHasReplacementAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->lpaHasReplacementAttorney());
    }

    public function testLpaHasReplacementAttorneyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->lpaHasReplacementAttorney(0));
    }

    public function testLpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)
            ->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY);
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally());
    }

    public function testLpaPrimaryAttorneysMakeDecisionJointly()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_JOINTLY);
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->lpaPrimaryAttorneysMakeDecisionJointly());
    }

    public function testLpaPrimaryAttorneysMakeDecisionDepends()
    {
        $lpa = FixturesData::getPfLpa();
        FixturesData::getPrimaryAttorneyDecisions($lpa)->set('how', AbstractDecisions::LPA_DECISION_HOW_DEPENDS);
        $stateChecker = new StateChecker($lpa);
        $this->assertTrue($stateChecker->lpaPrimaryAttorneysMakeDecisionDepends());
    }

    public function testLpaHasPrimaryAttorney()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasPrimaryAttorney());
    }

    public function testLpaHasPrimaryAttorneyIndex()
    {
        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasPrimaryAttorney(0));
    }

    public function testLpaHasTrustCorporation()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('primaryAttorneys', [new TrustCorporation()]);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasTrustCorporation());

        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('replacementAttorneys', [new TrustCorporation()]);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasTrustCorporation());

        $lpa = FixturesData::getHwLpa();
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertFalse($stateChecker->testLpaHasTrustCorporation());
    }

    public function testLpaHasTrustCorporationPrimary()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('primaryAttorneys', [new TrustCorporation()]);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertTrue($stateChecker->testLpaHasTrustCorporation('primary'));
        $this->assertFalse($stateChecker->testLpaHasTrustCorporation('replacement'));
    }

    public function testLpaHasTrustCorporationReplacement()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->get('document')->set('replacementAttorneys', [new TrustCorporation()]);
        $stateChecker = new TestableStateChecker($lpa);
        $this->assertFalse($stateChecker->testLpaHasTrustCorporation('primary'));
        $this->assertTrue($stateChecker->testLpaHasTrustCorporation('replacement'));
    }
}
