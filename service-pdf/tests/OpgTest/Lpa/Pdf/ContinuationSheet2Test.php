<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\Pdf\ContinuationSheet2;
use Opg\Lpa\Pdf\Traits\LongContentTrait;

class ContinuationSheet2Test extends AbstractFormTestClass
{
    use LongContentTrait;

    public function testGenerateInstructions()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->instruction = 'Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here';

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $lpa->document->instruction, 2);

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

    public function testGeneratePreferences()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->preference = 'Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here Some long preferences here';

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_PREFERENCES, $lpa->document->preference, 2);

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
            'cs2-is' => "preferences",
            'cs2-content' => "\r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here                               ",
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

    public function testGeneratePrimaryAttorneyDecisions()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->howDetails = 'Some content here';

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS, $lpa->document->primaryAttorneyDecisions->howDetails, 1);

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
            'cs2-is' => "decisions",
            'cs2-content' => "\r\nSome content here                                                                   ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsWhenLastHowDepends()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails'  => 'test test',
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act joint for some decisions, joint and several for    \r\nother decisions, as below:                                                          \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsWhenLastHowJointlyAndSeverally()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
            'howDetails'  => 'test test',
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsWhenLastHowJointly()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'  => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\n",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsWhenDepends()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
            'whenDetails' => 'test test',
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateSingleRAWhenFirst()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\n",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateSingleRAWhenLast()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when' => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateSingleRAWhenDepends()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
            'whenDetails' => 'test test',
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsPAsHowJointlyHowJointlyAndSeverally()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsPAsHowJointlyHowDepends()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails'  => 'test test',
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly for some decisions and jointly and         \r\nseverally for others, as below:                                                     \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGenerateMultiRAsPAsHowJointlyHowJointly()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ];

        $content = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);

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
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\n",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $numberOfPages, $formattedLpaRef, $templateFileName, $strikeThroughs, $constituentPdfs, $data, $pageShift);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }
}
