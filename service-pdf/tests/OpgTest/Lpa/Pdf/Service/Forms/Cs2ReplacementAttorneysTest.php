<?php

namespace OpgTest\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\Pdf\Service\Forms\Cs2ReplacementAttorneys;
use mikehaertl\pdftk\Pdf;

class Cs2ReplacementAttorneysTest extends AbstractFormTestClass
{
    public function testGenerateMultiRAsWhenLastHowDepends()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails'  => 'test test',
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act joint for some decisions, joint and several for    \r\nother decisions, as below:                                                          \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateMultiRAsWhenLastHowJointlyAndSeverally()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys to step in only when none of the original attorneys can act   \r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateMultiRAsWhenLastHowJointly()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        $this->assertEmpty($interFileStack);
    }

    public function testGenerateMultiRAsWhenDepends()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
            'whenDetails' => 'test test',
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateSingleRAWhenFirst()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        $this->assertEmpty($interFileStack);
    }

    public function testGenerateSingleRAWhenLast()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorney to step in only when none of the original attorneys can act    ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateSingleRAWhenDepends()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        //  Reduce the number of primary attorneys down to one
        array_splice($lpa->document->replacementAttorneys, 1);

        $lpa->document->replacementAttorneyDecisions = [
            'when'        => ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS,
            'whenDetails' => 'test test',
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nHow replacement attorneys will replace the original attorneys:                      \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateMultiRAsPAsHowJointlyHowJointlyAndSeverally()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly and severally                              ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateMultiRAsPAsHowJointlyHowDepends()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS,
            'howDetails'  => 'test test',
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        //  Assert the keys in the interFileStack
        $this->assertArrayHasKey('CS2', $interFileStack);
        $this->assertCount(1, $interFileStack['CS2']);

        $this->verifyTmpFileNames($lpa, $interFileStack['CS2'], 'CS2');

        $pdf = $cs2->getPdfObject();
        $this->assertInstanceOf(Pdf::class, $pdf);

        //  Confirm that the form data is as expected
        $expectedData = [
            'cs2-is' => "how-replacement-attorneys-step-in",
            'cs2-content' => "\r\nReplacement attorneys are to act jointly for some decisions and jointly and         \r\nseverally for others, as below:                                                     \r\ntest test                                                                           ",
            'cs2-donor-full-name' => "Mrs Nancy Garrison",
            'cs2-continued' => "",
            'cs2-footer-right' => "LPC Continuation sheet 2 (07.15)",
        ];

        $this->assertEquals($expectedData, $this->extractPdfFormData($pdf));
    }

    public function testGenerateMultiRAsPAsHowJointlyHowJointly()
    {
        $lpa = $this->getLpa();

        //  Adapt the LPA as required
        $lpa->document->primaryAttorneyDecisions->how = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY;

        $lpa->document->replacementAttorneyDecisions = [
            'how'         => ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
        ];

        $cs2 = new Cs2ReplacementAttorneys($lpa);

        $interFileStack = $cs2->generate();

        $this->assertEmpty($interFileStack);
    }
}
