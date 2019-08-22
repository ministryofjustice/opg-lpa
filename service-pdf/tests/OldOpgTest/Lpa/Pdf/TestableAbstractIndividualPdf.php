<?php

namespace OpgTest\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\AbstractIndividualPdf;

class TestableAbstractIndividualPdf extends AbstractIndividualPdf
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LP1F.pdf';

    public function __construct(?Lpa $lpa = null, array $options = [])
    {


        parent::__construct($lpa, $options);
    }

    /**
     * Empty create method so that the class can be instantiated
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa) { }

    /**
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     */
    public function drawStrikeThroughsAndBlanks() : void
    {
        parent::drawStrikeThroughsAndBlanks();
    }

    public function addStrikeThrough($areaReference, $pageNumber = 1) : void
    {
        parent::addStrikeThrough($areaReference, $pageNumber);
    }

    public function addBlank($areaReference, $pageNumber = 1)
    {
        return parent::addBlank($areaReference, $pageNumber);
    }

    public function setPdfFile(string $path) : void
    {
        $this->pdfFile = $path;
    }
}
