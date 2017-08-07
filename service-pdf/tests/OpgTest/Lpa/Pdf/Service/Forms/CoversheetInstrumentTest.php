<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\CoversheetInstrument;

class CoversheetInstrumentTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $coversheetInstrument = new CoversheetInstrument($lpa);

        $interFileStack = $coversheetInstrument->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $coversheetInstrument = new CoversheetInstrument($lpa);

        $interFileStack = $coversheetInstrument->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }
}
