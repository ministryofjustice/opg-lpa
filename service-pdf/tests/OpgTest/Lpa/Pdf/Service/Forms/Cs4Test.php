<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs4;

class Cs4Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs4 = new Cs4($lpa, 12345678);

        $interFileStack = $cs4->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS4', $interFileStack);
        $this->assertCount(1, $interFileStack['CS4']);

        $this->verifyFileNames($lpa, $interFileStack['CS4'], 'CS4');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs4 = new Cs4($lpa, 12345678);

        $interFileStack = $cs4->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS4', $interFileStack);
        $this->assertCount(1, $interFileStack['CS4']);

        $this->verifyFileNames($lpa, $interFileStack['CS4'], 'CS4');
    }
}
