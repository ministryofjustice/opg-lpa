<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\ContinuationSheet3;

class ContinuationSheet3Test extends AbstractPdfTestClass
{
    public function testGeneratePF()
    {
        $lpa = $this->getLpa();
        $pdf = new ContinuationSheet3($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPC_Continuation_Sheet_3.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('blank.pdf'),
            ],
        ];

        $data = [
            'cs3-donor-full-name' => "Mrs Nancy Garrison",
            'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet3.pdf');
    }

    public function testGenerateHW()
    {
        $lpa = $this->getLpa(false);
        $pdf = new ContinuationSheet3($lpa);

        //  Set up the expected data for verification
        $formattedLpaRef = 'A510 7295 5716';
        $templateFileName = 'LPC_Continuation_Sheet_3.pdf';

        $strikeThroughs = [];

        $constituentPdfs = [
            'start' => [
                $this->getFullTemplatePath('blank.pdf'),
            ],
        ];

        $data = [
            'cs3-donor-full-name' => "Mrs Nancy Garrison",
            'cs3-footer-right' => "LPC Continuation sheet 3 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift, $formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet3.pdf');
    }
}
