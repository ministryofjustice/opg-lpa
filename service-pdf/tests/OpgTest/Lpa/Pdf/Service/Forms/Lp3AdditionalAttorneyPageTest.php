<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp3AdditionalAttorneyPage;
use mikehaertl\pdftk\Pdf;

class Lp3AdditionalAttorneyPageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $interFileStack = $lp3AdditionalAttorneyPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneys', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalAttorneys']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneys'], 'AdditionalAttorneys');

        $pdf = $lp3AdditionalAttorneyPage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-last' => "Standard Trust",
            'lpa-document-primaryAttorneys-0-address-address1' => "1 Laburnum Place",
            'lpa-document-primaryAttorneys-0-address-address2' => "Sketty",
            'lpa-document-primaryAttorneys-0-address-address3' => "Swansea, Abertawe",
            'lpa-document-primaryAttorneys-0-address-postcode' => "SA2 8HT",
            'lpa-document-primaryAttorneys-1-name-title' => "Mr",
            'lpa-document-primaryAttorneys-1-name-first' => "Elliot",
            'lpa-document-primaryAttorneys-1-name-last' => "Sanders",
            'lpa-document-primaryAttorneys-1-address-address1' => "12 Church Lane",
            'lpa-document-primaryAttorneys-1-address-address2' => "Brierfield",
            'lpa-document-primaryAttorneys-1-address-address3' => "Lancashire",
            'lpa-document-primaryAttorneys-1-address-postcode' => "L21 4WL",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));

        //  TODO - Expand this test to check the strike through lines also
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp3AdditionalAttorneyPage = new Lp3AdditionalAttorneyPage($lpa);

        $interFileStack = $lp3AdditionalAttorneyPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalAttorneys', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalAttorneys']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalAttorneys'], 'AdditionalAttorneys');

        $pdf = $lp3AdditionalAttorneyPage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'how-attorneys-act' => "jointly-attorney-severally",
            'lpa-document-primaryAttorneys-0-name-title' => "Mr",
            'lpa-document-primaryAttorneys-0-name-first' => "Elliot",
            'lpa-document-primaryAttorneys-0-name-last' => "Sanders",
            'lpa-document-primaryAttorneys-0-address-address1' => "12 Church Lane",
            'lpa-document-primaryAttorneys-0-address-address2' => "Brierfield",
            'lpa-document-primaryAttorneys-0-address-address3' => "Lancashire",
            'lpa-document-primaryAttorneys-0-address-postcode' => "L21 4WL",
            'footer-right-page-three' => "LP3 People to notify (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));

        //  TODO - Expand this test to check the strike through lines also
    }
}
