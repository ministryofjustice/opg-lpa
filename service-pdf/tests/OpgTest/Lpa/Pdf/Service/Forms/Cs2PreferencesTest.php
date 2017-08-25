<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Cs2Preferences;
use mikehaertl\pdftk\Pdf;

class Cs2PreferencesTest extends AbstractFormTestClass
{
    public function testGenerate()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->preference = 'Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here';

        $cs2 = new Cs2Preferences($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "preferences",
            'cs2-content' => "\r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here                               ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "(Continued)",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
