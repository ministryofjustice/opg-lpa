<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs2PrimaryAttorneyDecisions;
use mikehaertl\pdftk\Pdf;

class Cs2PrimaryAttorneyDecisionsTest extends AbstractFormTestClass
{
    public function testGenerate()
    {
        $lpa = $this->getLpa();

        //  Update the LPA data to ensure the continuation sheet is generated
        $lpa->document->primaryAttorneyDecisions->how = 'depends';
        $lpa->document->primaryAttorneyDecisions->howDetails = 'Some content here';

        $cs2 = new Cs2PrimaryAttorneyDecisions($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "decisions",
            'cs2-content' => "\r\nSome content here                                                                   ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
