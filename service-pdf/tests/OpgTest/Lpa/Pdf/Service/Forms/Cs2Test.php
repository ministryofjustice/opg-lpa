<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\AbstractForm;
use Opg\Lpa\Pdf\Service\Forms\Cs2;

class Cs2Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs2 = new Cs2($lpa, AbstractForm::CONTENT_TYPE_ATTORNEY_DECISIONS, 'Some content here');

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs2 = new Cs2($lpa, AbstractForm::CONTENT_TYPE_ATTORNEY_DECISIONS, 'Some content here');

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');
    }

    public function testGeneratePFLongInstructions()
    {
        $lpa = $this->getLpa();
        $cs2 = new Cs2($lpa, AbstractForm::CONTENT_TYPE_INSTRUCTIONS, 'Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here
        Some long instructions here Some long instructions here Some long instructions here Some long instructions here');

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(2, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');
    }
}
