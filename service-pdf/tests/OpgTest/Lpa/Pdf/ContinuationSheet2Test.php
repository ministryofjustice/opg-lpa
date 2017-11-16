<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\Pdf\ContinuationSheet2;

class ContinuationSheet2Test extends AbstractFormTestClass
{
    public function testGenerateInstructions()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $content = $lpa->document->instruction = 'Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here';

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $content, 2);

        //  Set up the expected data for verification
        $numberOfPages = 1;
        $formattedLpaRef = 'A510 7295 5715';
        $templateFileName = 'LPC_Continuation_Sheet_2.pdf';

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
            'cs2-is' => "instructions",
            'cs2-content' => "\r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here                             ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "(Continued)",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }
}
