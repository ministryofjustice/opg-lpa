<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp3AdditionalAttorneyPage;

class Lp3AdditionalAttorneyPageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $interFileStack = $lp3AdditionalAttorneyPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneys', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalAttorneys']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneys'], 'AdditionalAttorneys');
    }

    public function testGeneratePFReturnBlankTooFewAttorneys()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $this->assertNull($lp3AdditionalAttorneyPage->generate());
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $interFileStack = $lp3AdditionalAttorneyPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneys', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalAttorneys']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneys'], 'AdditionalAttorneys');
    }

    public function testGenerateHWReturnBlankTooFewAttorneys()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA data as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->primaryAttorneys, 1);
        array_splice($lpa->document->whoIsRegistering, 1);

        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $this->assertNull($lp3AdditionalAttorneyPage->generate());
    }
}
