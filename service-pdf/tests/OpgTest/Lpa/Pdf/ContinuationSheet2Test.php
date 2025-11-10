<?php

namespace OpgTest\Lpa\Pdf;

use Exception;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\Pdf\ContinuationSheet2;
use Opg\Lpa\Pdf\Traits\LongContentTrait;

class ContinuationSheet2Test extends AbstractPdfTestCase
{
    use LongContentTrait;

    private $templateFileName = 'LPC_Continuation_Sheet_2.pdf';

    private function getConstituentPdfs()
    {
        return [
            'start' => [
                $this->getFullTemplatePath('blank.pdf'),
            ]
        ];
    }

    public function testConstructorExceptionContentPageLessThan1()
    {
        $this->expectExceptionMessage('The requested content page must be a positive integer');

        $lpa = $this->getLpa();
        $contentPage = 0;
        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $lpa->document->instruction, $contentPage);
    }

    public function testConstructorExceptionContentPageNonNumeric()
    {
        $this->expectExceptionMessage('The requested content page must be a positive integer');

        $lpa = $this->getLpa();
        $contentPage = '1';
        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $lpa->document->instruction, $contentPage);
    }

    public function testConstructorExceptionContentPage1()
    {
        $this->expectExceptionMessage('Page 1 of the preferences and instructions can not be displayed on continuation sheet 2');

        $lpa = $this->getLpa();
        $contentPage = 1;
        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $lpa->document->instruction, $contentPage);
    }

    public function testGenerateInstructions()
    {
        $lpa = $this->getLpa();

        //  Set up the content
        //  Adapt the LPA as required
        $lpa->document->instruction = 'Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here Some long instructions here';

        $pdf = new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_INSTRUCTIONS, $lpa->document->instruction, 2);

        $data = [
            'cs2-is' => "instructions",
            'cs2-content' => "\r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here Some long instructions here \r\nSome long instructions here Some long instructions here                             ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "(Continued)",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "preferences",
            'cs2-content' => "\r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here Some long preferences here    \r\nSome long preferences here Some long preferences here                               ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "(Continued)",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "decisions",
            'cs2-content' => "\r\nSome content here                                                                   ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

        //  Test the generated filename created
        $pdfFile = $pdf->generate();

        $this->verifyTmpFileName($lpa, $pdfFile, 'ContinuationSheet2.pdf');
    }

    public function testGeneratePrimaryAttorneyDecisionsWhenWhitespace()
    {
        $lpa = $this->getLpa();

        $lpa->document->primaryAttorneyDecisions->howDetails = ' ';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page 1 can not be generated for content type decisions');

        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS, $lpa->document->primaryAttorneyDecisions->howDetails, 1);
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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act joint for some decisions, joint and several for    \r\nother decisions, as below:                                                          \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $this->assertEquals('', $content);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page 1 can not be generated for content type how-replacement-attorneys-step-in');

        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);
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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $this->assertEquals('', $content);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page 1 can not be generated for content type how-replacement-attorneys-step-in');

        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);
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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $data = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly for some decisions and jointly and         \r\nseverally for others, as below:                                                     \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $pageShift = 0;

        $this->verifyExpectedPdfData($pdf, $this->templateFileName, $this->strikeThroughTargets, $this->blankTargets, $this->getConstituentPdfs(), $data, $pageShift, $this->formattedLpaRef);

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

        $this->assertEquals('', $content);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page 1 can not be generated for content type how-replacement-attorneys-step-in');

        new ContinuationSheet2($lpa, ContinuationSheet2::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN, $content, 1);
    }
}
