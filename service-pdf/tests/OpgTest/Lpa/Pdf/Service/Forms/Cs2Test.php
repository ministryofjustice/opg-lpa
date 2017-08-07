<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\AbstractForm;
use Opg\Lpa\Pdf\Service\Forms\Cs2;
use mikehaertl\pdftk\Pdf;

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

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $cs2 = new Cs2($lpa, AbstractForm::CONTENT_TYPE_ATTORNEY_DECISIONS, 'Some content here');

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

    public function testGeneratePFLongInstructions()
    {
        $lpa = $this->getLpa();
        $cs2 = new Cs2($lpa, AbstractForm::CONTENT_TYPE_INSTRUCTIONS, 'Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here');

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(2, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "instructions",
            'cs2-content' => "\r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "(Continued)",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
