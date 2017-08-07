<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalAttorneySignaturePage;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalAttorneySignaturePageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalAttorneySignaturePage = new Lp1AdditionalAttorneySignaturePage($lpa);

        $interFileStack = $lp1AdditionalAttorneySignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneySignature', $interFileStack);
        $this->assertCount(4, $interFileStack['AdditionalAttorneySignature']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneySignature'], 'AdditionalAttorneySignature');

        $pdf = $lp1AdditionalAttorneySignaturePage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'signature-attorney-name-title' => "Ms",
            'signature-attorney-name-first' => "Erica",
            'signature-attorney-name-last' => "Schmidt",
            'footer-instrument-right-additional' => "LP1F Property and financial affairs (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalAttorneySignaturePage = new Lp1AdditionalAttorneySignaturePage($lpa);

        $interFileStack = $lp1AdditionalAttorneySignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneySignature', $interFileStack);
        $this->assertCount(4, $interFileStack['AdditionalAttorneySignature']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneySignature'], 'AdditionalAttorneySignature');

        $pdf = $lp1AdditionalAttorneySignaturePage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'signature-attorney-name-title' => "Ms",
            'signature-attorney-name-first' => "Erica",
            'signature-attorney-name-last' => "Schmidt",
            'footer-instrument-right-additional' => "LP1H Health and welfare (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
