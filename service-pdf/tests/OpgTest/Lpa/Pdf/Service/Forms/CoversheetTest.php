<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Coversheet;

class CoversheetTest extends AbstractFormTestClass
{
    public function testGenerate()
    {
        $lpa = $this->getLpa();
        $coversheet = new Coversheet($lpa);

        $interFileStack = $coversheet->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $coversheet = new Coversheet($lpa);

        $interFileStack = $coversheet->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }

    public function testGeneratePFDraftCoversheet()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA to make it incomplete
        $lpa->payment = null;

        $coversheet = new Coversheet($lpa);

        $interFileStack = $coversheet->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }

    public function testGenerateHWDraftCoversheet()
    {
        $lpa = $this->getLpa(false);

        //  Adapt the LPA to make it incomplete
        $lpa->payment = null;

        $coversheet = new Coversheet($lpa);

        $interFileStack = $coversheet->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }
}
