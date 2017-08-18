<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs4;
use mikehaertl\pdftk\Pdf;

class Cs4Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $cs4 = new Cs4($lpa);

        $interFileStack = $cs4->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS4', $interFileStack);
        $this->assertCount(1, $interFileStack['CS4']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS4'], 'CS4');

        $pdf = $cs4->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs4-trust-corporation-company-registration-number' => "678437685",
            'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
