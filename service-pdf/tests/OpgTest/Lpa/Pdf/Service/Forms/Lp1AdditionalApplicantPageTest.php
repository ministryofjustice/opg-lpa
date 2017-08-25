<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\Pdf\Service\Forms\Lp1AdditionalApplicantPage;
use mikehaertl\pdftk\Pdf;

class Lp1AdditionalApplicantPageTest extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $lp1AdditionalApplicantPage = new Lp1AdditionalApplicantPage($lpa);

        $interFileStack = $lp1AdditionalApplicantPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicant', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicant']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicant'], 'AdditionalApplicant');

        $pdf = $lp1AdditionalApplicantPage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'applicant-0-name-last' => "Standard Trust",
            'applicant-1-name-title' => "Mr",
            'applicant-1-name-first' => "Elliot",
            'applicant-1-name-last' => "Sanders",
            'applicant-1-dob-date-day' => "10",
            'applicant-1-dob-date-month' => "10",
            'applicant-1-dob-date-year' => "1987",
            'who-is-applicant' => "attorney",
            'footer-registration-right-additional' => "LP1F Register your LPA (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));

        //  Confirm the crossed lines data is as expected
        $expectedCrossedLines = [
            0 => [
                'additional-applicant-2-pf',
                'additional-applicant-3-pf',
            ],
        ];

        $this->assertEquals($expectedCrossedLines, $this->extractCrossedLines($lp1AdditionalApplicantPage));
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $lp1AdditionalApplicantPage = new Lp1AdditionalApplicantPage($lpa);

        $interFileStack = $lp1AdditionalApplicantPage->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('AdditionalApplicant', $interFileStack);
        $this->assertCount(1, $interFileStack['AdditionalApplicant']);

        $this->verifyTmpFileNames($lpa, $interFileStack['AdditionalApplicant'], 'AdditionalApplicant');

        $pdf = $lp1AdditionalApplicantPage->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'applicant-0-name-title' => "Mr",
            'applicant-0-name-first' => "Elliot",
            'applicant-0-name-last' => "Sanders",
            'applicant-0-dob-date-day' => "10",
            'applicant-0-dob-date-month' => "10",
            'applicant-0-dob-date-year' => "1987",
            'who-is-applicant' => "attorney",
            'footer-registration-right-additional' => "LP1H Register your LPA (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));

        //  Confirm the crossed lines data is as expected
        $expectedCrossedLines = [
            0 => [
                'additional-applicant-1-hw',
                'additional-applicant-2-hw',
                'additional-applicant-3-hw',
            ],
        ];

        $this->assertEquals($expectedCrossedLines, $this->extractCrossedLines($lp1AdditionalApplicantPage));
    }
}
