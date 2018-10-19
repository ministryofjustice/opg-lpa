<?php

namespace OpgTest\Lpa\Pdf;

use mikehaertl\pdftk\Pdf;

class AbstractIndividualPdfTest extends AbstractPdfTestClass
{
    /**
     * @var string
     */
    private $testPdfPath;

    public function setUp()
    {
        parent::setUp();

        $tmpDirectory = $this->getTestDirectory() . 'tmp/';

        if(!file_exists($tmpDirectory)) {
            mkdir($tmpDirectory);
        }

        $this->testPdfPath = $tmpDirectory . 'AbstractIndividualPdfTest.pdf';

        // Copy the fixture file as the tests will modify what they operate on
        $this->assertTrue(copy('/app/tests/fixtures/Empty-LP1F.pdf', $this->testPdfPath),
            'Failed to copy the fixture PDF needed for the test');
    }

    public function tearDown() : void
    {
        if(file_exists($this->testPdfPath)) {
            unlink($this->testPdfPath);
        }
    }

    public function getUncompressedPdfContents()
    {
        // Uncompress the file
        $resultPdf = new Pdf($this->testPdfPath);
        $resultPdf->compress(false)->saveAs($this->testPdfPath);

        return file_get_contents($this->testPdfPath);
    }

    public function testDrawStrikeThroughsAndBlanksDrawLine() : void
    {
        $pdf = new TestableAbstractIndividualPdf();

        $pdf->setPdfFile($this->testPdfPath);

        $pdf->addStrikeThrough('applicant-2-pf', 17);

        $pdf->drawStrikeThroughsAndBlanks();

        $pdfContents = $this->getUncompressedPdfContents();

        // Check that a 10 width black line is drawn with the correct coordinates
        $this->assertNotFalse(strpos($pdfContents, "10.000000 w 0.000000 0.000000 0.000000 RG \n" .
            "42.000000 155.000000 m\n" .
            "283.000000 253.000000 l\n"));
    }

    public function testDrawStrikeThroughsAndBlanksDrawBlank() : void
    {
        $pdf = new TestableAbstractIndividualPdf();

        $pdf->setPdfFile($this->testPdfPath);

        $pdf->addBlank('applicant-signature-1-pf', 20);

        $pdf->drawStrikeThroughsAndBlanks();

        $pdfContents = $this->getUncompressedPdfContents();

        // Check that a white rectangle is drawn with the correct coordinates
        $this->assertNotFalse(strpos($pdfContents, "1.000000 1.000000 1.000000 rg\n" .
            "297.000000 382.000000 263.000000 129.000000 re f\n"));
    }
}
