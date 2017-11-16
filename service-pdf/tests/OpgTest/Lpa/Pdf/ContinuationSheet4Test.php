<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\ContinuationSheet4;

class ContinuationSheet4Test extends AbstractFormTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $pdf = new ContinuationSheet4($lpa);

        //  Set up the expected data for verification
        $numberOfPages = 1;
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

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
            'cs4-trust-corporation-company-registration-number' => "678437685",
            'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $numberOfPages, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet4.pdf');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $pdf = new ContinuationSheet4($lpa);

        //  Set up the expected data for verification
        $numberOfPages = 1;
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

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
            'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $numberOfPages, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet4.pdf');
    }
}
