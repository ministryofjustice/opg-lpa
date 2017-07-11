<?php

namespace OpgTest\Lpa\DataModel\Lpa;

use Opg\Lpa\DataModel\Lpa\StateChecker;

class TestableStateChecker extends StateChecker
{
    public function testPaymentResolved()
    {
        return parent::paymentResolved();
    }

    public function testIsWhoAreYouAnswered()
    {
        return parent::isWhoAreYouAnswered();
    }

    public function testLpaHasCorrespondent()
    {
        return parent::lpaHasCorrespondent();
    }

    public function testLpaHasApplicant()
    {
        return parent::lpaHasApplicant();
    }

    public function testLpaHasFinishedCreation()
    {
        return parent::lpaHasFinishedCreation();
    }

    public function testLpaHasCreated()
    {
        return parent::lpaHasCreated();
    }

    public function testLpaHasPeopleToNotify($index = null)
    {
        return parent::lpaHasPeopleToNotify($index);
    }

    public function testLpaHasCertificateProvider()
    {
        return parent::lpaHasCertificateProvider();
    }

    public function testLpaHowReplacementAttorneysMakeDecisionHasValue()
    {
        return parent::lpaHowReplacementAttorneysMakeDecisionHasValue();
    }

    public function testLpaReplacementAttorneysMakeDecisionJointlyAndSeverally()
    {
        return parent::lpaReplacementAttorneysMakeDecisionJointlyAndSeverally();
    }

    public function testLpaReplacementAttorneysMakeDecisionJointly()
    {
        return parent::lpaReplacementAttorneysMakeDecisionJointly();
    }

    public function testLpaReplacementAttorneysMakeDecisionDepends()
    {
        return parent::lpaReplacementAttorneysMakeDecisionDepends();
    }

    public function testLpaHowPrimaryAttorneysMakeDecisionHasValue()
    {
        return parent::lpaHowPrimaryAttorneysMakeDecisionHasValue();
    }

    public function testLpaHasPrimaryAttorney($index = null)
    {
        return parent::lpaHasPrimaryAttorney($index);
    }

    public function testLpaHasTrustCorporation($whichGroup = null)
    {
        return parent::lpaHasTrustCorporation($whichGroup);
    }

    public function testLpaHasLifeSustaining()
    {
        return parent::lpaHasLifeSustaining();
    }

    public function testLpaHasWhenLpaStarts()
    {
        return parent::lpaHasWhenLpaStarts();
    }

    public function testLpaHasDonor()
    {
        return parent::lpaHasDonor();
    }

    public function testLpaHasType()
    {
        return parent::lpaHasType();
    }

    public function testLpaHasDocument()
    {
        return parent::lpaHasDocument();
    }
}
