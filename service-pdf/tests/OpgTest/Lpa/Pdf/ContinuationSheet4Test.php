<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\ContinuationSheet4;

class ContinuationSheet4Test extends AbstractPdfTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $pdf = new ContinuationSheet4($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('blank.pdf'),
            ],
        ];

        $data = [
            'cs4-trust-corporation-company-registration-number' => "678437685",
            'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet4.pdf');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $pdf = new ContinuationSheet4($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LPC_Continuation_Sheet_4.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('blank.pdf'),
            ],
        ];

        $data = [
            'cs4-footer-right' => "LPC Continuation sheet 4 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet4.pdf');
    }
}
