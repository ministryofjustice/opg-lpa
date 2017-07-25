<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalApplicantSignaturePage;

class Lp1AdditionalApplicantSignaturePageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalApplicantSignaturePage = new Lp1AdditionalApplicantSignaturePage($lpa);

        $interFileStack = $lp1AdditionalApplicantSignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicantSignature', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicantSignature']);

        $this->verifyFileNames($lpa, $interFileStack['AdditionalApplicantSignature'], 'AdditionalApplicantSignature');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalApplicantSignaturePage = new Lp1AdditionalApplicantSignaturePage($lpa);

        $interFileStack = $lp1AdditionalApplicantSignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicantSignature', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicantSignature']);

        $this->verifyFileNames($lpa, $interFileStack['AdditionalApplicantSignature'], 'AdditionalApplicantSignature');
    }
}
