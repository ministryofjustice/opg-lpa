<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs1;

class Cs1Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs1 = new Cs1($lpa, $this->getActorTypes());

        $interFileStack = $cs1->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS1', $interFileStack);
        $this->assertCount(2, $interFileStack['CS1']);

        $this->verifyFileNames($lpa, $interFileStack['CS1'], 'CS1');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs1 = new Cs1($lpa, $this->getActorTypes());

        $interFileStack = $cs1->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS1', $interFileStack);
        $this->assertCount(2, $interFileStack['CS1']);

        $this->verifyFileNames($lpa, $interFileStack['CS1'], 'CS1');
    }

    private function getActorTypes()
    {
        return [
            'primaryAttorney',
            'replacementAttorney',
            'peopleToNotify'
        ];
    }
}
