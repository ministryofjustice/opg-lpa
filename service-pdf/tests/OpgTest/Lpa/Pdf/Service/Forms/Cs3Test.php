<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs3;

class Cs3Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs3 = new Cs3($lpa);

        $interFileStack = $cs3->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS3', $interFileStack);
        $this->assertCount(1, $interFileStack['CS3']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS3'], 'CS3');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs3 = new Cs3($lpa);

        $interFileStack = $cs3->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS3', $interFileStack);
        $this->assertCount(1, $interFileStack['CS3']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS3'], 'CS3');
    }
}
