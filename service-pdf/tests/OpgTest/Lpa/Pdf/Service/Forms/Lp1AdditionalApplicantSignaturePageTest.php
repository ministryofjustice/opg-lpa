<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalApplicantSignaturePage;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalApplicantSignaturePageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalApplicantSignaturePage = new Lp1AdditionalApplicantSignaturePage($lpa);

        $interFileStack = $lp1AdditionalApplicantSignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicantSignature', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicantSignature']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicantSignature'], 'AdditionalApplicantSignature');

        $pdf = $lp1AdditionalApplicantSignaturePage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'footer-registration-right-additional' => "LP1F Register your LPA (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalApplicantSignaturePage = new Lp1AdditionalApplicantSignaturePage($lpa);

        $interFileStack = $lp1AdditionalApplicantSignaturePage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicantSignature', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicantSignature']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicantSignature'], 'AdditionalApplicantSignature');

        $pdf = $lp1AdditionalApplicantSignaturePage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'footer-registration-right-additional' => "LP1H Register your LPA (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }
}
