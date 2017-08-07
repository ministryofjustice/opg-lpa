<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalApplicantPage;

class Lp1AdditionalApplicantPageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalApplicantPage = new Lp1AdditionalApplicantPage($lpa);

        $interFileStack = $lp1AdditionalApplicantPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicant', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicant']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicant'], 'AdditionalApplicant');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalApplicantPage = new Lp1AdditionalApplicantPage($lpa);

        $interFileStack = $lp1AdditionalApplicantPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicant', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicant']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicant'], 'AdditionalApplicant');
    }
}
