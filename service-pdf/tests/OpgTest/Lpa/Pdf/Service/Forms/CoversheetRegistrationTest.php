<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\CoversheetRegistration;

class CoversheetRegistrationTest extends AbstractFormTestClass
{
    public function testGenerate()
    {
        $lpa = $this->getLpa();
        $coversheetRegistration = new CoversheetRegistration($lpa);

        $interFileStack = $coversheetRegistration->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $coversheetRegistration = new CoversheetRegistration($lpa);

        $interFileStack = $coversheetRegistration->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('Coversheet', $interFileStack);
        $this->assertCount(1, $interFileStack['Coversheet']);

        $this->verifyTmpFileNames($lpa, $interFileStack['Coversheet'], 'Coversheet');
    }
}
