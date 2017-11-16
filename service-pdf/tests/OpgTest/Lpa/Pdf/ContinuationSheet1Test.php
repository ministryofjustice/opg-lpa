<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\ContinuationSheet1;

class ContinuationSheet1Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();

        //  Set up the actor groups
        $actorGroups = [
            'primaryAttorney' => [
                $lpa->document->primaryAttorneys[3],
                $lpa->document->primaryAttorneys[5],
            ],
        ];

        $pdf = new ContinuationSheet1($lpa, $actorGroups);

        //  Set up the expected data for verification
        $numberOfPages = 1;
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPC_Continuation_Sheet_1.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                [
                    'pdf'   => $this->getFullTemplatePath('blank.pdf'),
                    'start' => 1,
                    'pages' => 1,
                ],
            ],
        ];

        $data = [
            'cs1-donor-full-name' => "Mrs Nancy Garrison",
            'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
            'cs1-0-is' => "primaryAttorney",
            'cs1-0-name-title' => "Dr",
            'cs1-0-name-first' => "Henry",
            'cs1-0-name-last' => "Taylor",
            'cs1-0-address-address1' => "Lark Meadow Drive",
            'cs1-0-address-address2' => "Solihull",
            'cs1-0-address-address3' => "Birmingham",
            'cs1-0-address-postcode' => "B37 6NA",
            'cs1-0-dob-date-day' => "10",
            'cs1-0-dob-date-month' => "09",
            'cs1-0-dob-date-year' => "1973",
            'cs1-0-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'cs1-1-is' => "primaryAttorney",
            'cs1-1-name-title' => "Mr",
            'cs1-1-name-first' => "Elliot",
            'cs1-1-name-last' => "Sanders",
            'cs1-1-address-address1' => "12 Church Lane",
            'cs1-1-address-address2' => "Brierfield",
            'cs1-1-address-address3' => "Lancashire",
            'cs1-1-address-postcode' => "L21 4WL",
            'cs1-1-dob-date-day' => "10",
            'cs1-1-dob-date-month' => "10",
            'cs1-1-dob-date-year' => "1987",
            'cs1-1-email-address' => "\nopglpademo+ElliotSanders@gmail.com",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $numberOfPages, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet1.pdf');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);

        //  Set up the actor groups
        $actorGroups = [
            'primaryAttorney' => [
                $lpa->document->primaryAttorneys[3],
                $lpa->document->primaryAttorneys[4],
            ],
        ];

        $pdf = new ContinuationSheet1($lpa, $actorGroups);

        //  Set up the expected data for verification
        $numberOfPages = 1;
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LPC_Continuation_Sheet_1.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                [
                    'pdf'   => $this->getFullTemplatePath('blank.pdf'),
                    'start' => 1,
                    'pages' => 1,
                ],
            ],
        ];

        $data = [
            'cs1-donor-full-name' => "Mrs Nancy Garrison",
            'cs1-footer-right' => "LPC Continuation sheet 1 (07.15)",
            'cs1-0-is' => "primaryAttorney",
            'cs1-0-name-title' => "Dr",
            'cs1-0-name-first' => "Henry",
            'cs1-0-name-last' => "Taylor",
            'cs1-0-address-address1' => "Lark Meadow Drive",
            'cs1-0-address-address2' => "Solihull",
            'cs1-0-address-address3' => "Birmingham",
            'cs1-0-address-postcode' => "B37 6NA",
            'cs1-0-dob-date-day' => "10",
            'cs1-0-dob-date-month' => "09",
            'cs1-0-dob-date-year' => "1973",
            'cs1-0-email-address' => "\nopglpademo+HenryTaylor@gmail.com",
            'cs1-1-is' => "primaryAttorney",
            'cs1-1-name-title' => "Mr",
            'cs1-1-name-first' => "Elliot",
            'cs1-1-name-last' => "Sanders",
            'cs1-1-address-address1' => "12 Church Lane",
            'cs1-1-address-address2' => "Brierfield",
            'cs1-1-address-address3' => "Lancashire",
            'cs1-1-address-postcode' => "L21 4WL",
            'cs1-1-dob-date-day' => "10",
            'cs1-1-dob-date-month' => "10",
            'cs1-1-dob-date-year' => "1987",
            'cs1-1-email-address' => "\nopglpademo+ElliotSanders@gmail.com",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $numberOfPages, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet1.pdf');
    }
}
