<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs3;
use mikehaertl\pdftk\Pdf;

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

        $pdf = $cs3->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs3-donor-full-name' => "Mrs Nancy Garrison",
            'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
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

        $pdf = $cs3->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs3-donor-full-name' => "Mrs Nancy Garrison",
            'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
