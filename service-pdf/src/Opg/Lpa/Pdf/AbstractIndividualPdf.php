<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use mikehaertl\pdftk\Pdf as PdftkPdf;
use Exception;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Class AbstractIndividualPdf
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractIndividualPdf extends AbstractPdf
{
    /**
     * PDF template file name (without path) for this PDF object - value to be set in extending class
     *
     * @var
     */
    protected $templateFileName;

    /**
     * @var array
     */
    private $data = [];

    /**
     * Area references that should have a strike through
     *
     * @var array
     */
    private $strikeThroughTargets = [];

    /**
     * Area references that should have a blank drawn over them
     *
     * @var array
     */
    private $blankTargets = [];

    /**
     * @var array
     */
    private $constituentPdfs = [];

    /**
     * Integer value to track how the pages have shifted from their starting positions in the PDF, most likely
     * due to inserting content
     *
     * @var
     */
    protected $pageShift = 0;

    /**
     * @param Lpa|null $lpa
     * @param array $options
     * @throws Exception
     */
    public function __construct(Lpa $lpa = null, array $options = [])
    {
        //  Ensure that a template file was defined
        if (is_null($this->templateFileName)) {
            throw new Exception('PDF template file name must be defined to create ' . get_class($this));
        }

        //  If an LPA was provided confirm that the LPA provided can be used to generate this type of PDF
        if ($lpa instanceof Lpa) {
            //  If applicable check that the document can be created
            if (($this instanceof AbstractLp1 && !$lpa->canGenerateLP1())
                || ($this instanceof Lp3 && !$lpa->canGenerateLP3())
                || ($this instanceof Lpa120 && !$lpa->canGenerateLPA120())) {

                throw new Exception('LPA does not contain all the required data to generate ' . get_class($this));
            }
        }

        parent::__construct($lpa, $this->templateFileName, $options);
    }

    /**
     * Easy way to set the data to fill in the PDF - chainable
     *
     * @param $key
     * @param $value
     * @param bool $insertLeadingNewLine
     * @return $this
     */
    protected function setData($key, $value, $insertLeadingNewLine = false)
    {
        //  If applicable insert a new line char
        if ($insertLeadingNewLine === true) {
            $value = "\n" . $value;
        }

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Easy way to set a check box cross
     *
     * @param $key
     * @return $this
     */
    protected function setCheckBox($key)
    {
        return $this->setData($key, 'On');
    }

    /**
     * Set the footer content from the config
     *
     * @param $key
     * @param $type
     */
    public function setFooter($key, $type)
    {
        $this->setData($key, $this->config['footer'][$type]);
    }

    /**
     * Draw thick black lines through unused fields and hide extra signatures with a white rectangle
     *
     * Loads the PDF file modifies it and rewrites the file
     *
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     */
    protected function drawStrikeThroughsAndBlanks() : void
    {
        // If no strike throughs and blanks are specified skip the rest of this method
        if (empty($this->strikeThroughTargets) && empty($this->blankTargets)) {
            return;
        }

        // Check to see if drawing cross lines and blanks is disabled or not
        $disableStrikeThroughLines = false;
        $disableBlanks = false;

        if (isset($this->config['service']['disable_strike_through_lines'])) {
            $disableStrikeThroughLines = (bool)$this->config['service']['disable_strike_through_lines'];
        }

        if (isset($this->config['service']['disable_blanks'])) {
            $disableBlanks = (bool)$this->config['service']['disable_blanks'];
        }

        // If strike throughs and blanks are disabled skip the rest of this method
        if ($disableStrikeThroughLines && $disableBlanks) {
            return;
        }

        $pdf = new FPDI('P', 'pt');

        // Turn off line at top of PDF
        $pdf->setPrintHeader(false);

        $pageCount = $pdf->setSourceFile($this->pdfFile);

        // Import all the pages into the TCPDF pdf object
        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->addPage();

            $pageId = $pdf->importPage($i);

            $pdf->useTemplate($pageId);
        }

        $changesMade = false;

        if (!$disableStrikeThroughLines) {
            foreach ($this->strikeThroughTargets as $pageNo => $pageDrawingTargets) {
                $pdf->setPage($pageNo + 1);

                foreach ($pageDrawingTargets as $pageDrawingTarget) {
                    //  Get the coordinates for this target from the config (y is inverted)
                    if (isset($this->config['strike_throughs'][$pageDrawingTarget])) {
                        $targetStrikeThroughCoordinates = $this->config['strike_throughs'][$pageDrawingTarget];

                        $pdf->Line($targetStrikeThroughCoordinates['bx'],
                            $pdf->getPageHeight() - $targetStrikeThroughCoordinates['by'],
                            $targetStrikeThroughCoordinates['tx'],
                            $pdf->getPageHeight() - $targetStrikeThroughCoordinates['ty'],
                            ['width' => 10, 'color' => [0, 0, 0]]);

                        $changesMade = true;
                    }
                }
            }
        }

        if (!$disableBlanks) {
            foreach ($this->blankTargets as $pageNo => $pageDrawingTargets) {
                $pdf->setPage($pageNo + 1);

                foreach ($pageDrawingTargets as $pageDrawingTarget) {
                    //  Get the coordinates for this target from the config (y is inverted)
                    if (isset($this->config['blanks'][$pageDrawingTarget])) {
                        $blankCoordinates = $this->config['blanks'][$pageDrawingTarget];

                        $pdf->Rect($blankCoordinates['x1'],
                            $pdf->getPageHeight() - $blankCoordinates['y2'],
                            $blankCoordinates['x2'] - $blankCoordinates['x1'],
                            $blankCoordinates['y2'] - $blankCoordinates['y1'],
                            'F',
                            [],
                            [255, 255, 255]);

                        $changesMade = true;
                    }
                }
            }
        }

        if ($changesMade) {
            $pdf->Output($this->pdfFile, 'F');
        }

        $pdf->_destroy(true);
    }

    /**
     * Generate the PDF - this will save a copy to the file system
     *
     * @param bool $protect
     * @return string
     * @throws Exception
     */
    public function generate($protect = false)
    {
        $this->fillForm($this->data)
             ->needAppearances()
             ->flatten()
             ->saveAs($this->pdfFile);

        $this->drawStrikeThroughsAndBlanks();

        //  Process any constituent PDFs
        if (!empty($this->constituentPdfs)) {
            //  Sort the constituent PDFs into the required insertion order
            ksort($this->constituentPdfs, SORT_NATURAL);

            //  Loop through the constituent PDF settings and gradually adapt the document
            foreach ($this->constituentPdfs as $insertAfter => $constituentPdfsData) {
                foreach ($constituentPdfsData as $constituentPdfData) {
                    //  Execute the generation for this constituent
                    $constituentPdfFile = $constituentPdfData['pdf'];

                    //  If this PDF is an abstract PDF then trigger the generate and get the path
                    if ($constituentPdfFile instanceof AbstractPdf) {
                        $constituentPdfFile = $constituentPdfFile->generate();
                    }

                    $pdfMaster = new PdftkPdf([
                        'A' => $this->pdfFile,
                        'B' => $constituentPdfFile,
                    ]);

                    //  Get the start point, number of pages and work out the end point
                    $startAt = $constituentPdfData['start'];
                    $pages = $constituentPdfData['pages'];
                    $endAt = $startAt + $pages - 1;

                    //  Determine where the pages should be inserted taking into account the page shift
                    $insertPoint = (is_numeric($insertAfter) ? $insertAfter + $this->pageShift : $insertAfter);

                    if ($insertPoint == 'start') {
                        //  Pre append the constituent PDF to the master PDF
                        $pdfMaster->cat($startAt, $endAt, 'B')
                                  ->cat(1, 'end', 'A');
                    } else {
                        //  Insert the constituent pages in the specified position
                        $pdfMaster->cat(1, $insertPoint, 'A')
                                  ->cat($startAt, $endAt, 'B');

                        //  If the insert point was numeric then add the rest of the master file
                        //  If it wasn't numeric (e.g. 'end', etc) then do nothing
                        if (is_numeric($insertPoint)) {
                            $pdfMaster->cat($insertPoint + 1, 'end', 'A');
                        }
                    }

                    $pdfMaster->saveAs($this->pdfFile);

                    //  Update the page shift
                    $this->pageShift += $pages;
                }
            }
        }

        //  Trigger the parent
        return parent::generate($protect);
    }

    /**
     * Get the page shift that will be implemented before a specific page
     *
     * @param $originalPageNumber
     * @return int
     */
    protected function getPageShiftBeforePage($originalPageNumber)
    {
        //  Add the total calculated page shift
        $adjustedPageShift = $this->pageShift;

        //  Now deduct any shifted pages AFTER the original page number
        foreach ($this->constituentPdfs as $insertAfterPageNumber => $constituentPdfsToInsert) {
            if ($insertAfterPageNumber == 'end' || (is_numeric($insertAfterPageNumber) && $insertAfterPageNumber >= $originalPageNumber)) {
                $adjustedPageShift -= count($constituentPdfsToInsert);
            }
        }

        return $adjustedPageShift;
    }

    /**
     * Add a strike through line to the specified page
     *
     * @param $areaReference
     * @param int $pageNumber
     * @return $this
     */
    protected function addStrikeThrough($areaReference, $pageNumber = 1)
    {
        return $this->addDrawingTarget($this->strikeThroughTargets, $areaReference, $pageNumber);
    }

    /**
     * Draw a blank section on the specified page
     *
     * @param $areaReference
     * @param int $pageNumber
     * @return $this
     */
    protected function addBlank($areaReference, $pageNumber = 1)
    {
        return $this->addDrawingTarget($this->blankTargets, $areaReference, $pageNumber);
    }

    /**
     * Add a drawing target (strike through or blank) to the specified page
     *
     * @param array $drawingTargets
     * @param $areaReference
     * @param int $pageNumber
     * @return $this
     */
    private function addDrawingTarget(array &$drawingTargets, $areaReference, $pageNumber)
    {
        //  Adjust the page number for zero based indexes
        $pageNumber--;

        //  If a section doesn't exist for this page create one now
        if (!isset($drawingTargets[$pageNumber])) {
            $drawingTargets[$pageNumber] = [];
        }

        $drawingTargets[$pageNumber][] = $areaReference;

        return $this;
    }

    /**
     * Insert a number of pages of the constituent PDF after a specified page
     *
     * @param AbstractPdf|string $pdf
     * @param $start
     * @param $pages
     * @param $insertAfter
     * @throws Exception
     */
    protected function addConstituentPdf($pdf, $start, $pages, $insertAfter)
    {
        //  Ensure that the PDF is an expected type
        if (!is_string($pdf) && !$pdf instanceof AbstractPdf) {
            throw new Exception('Constituent PDF must be a type AbstractPdf or a string representing a file path');
        }

        //  Ensure that the start page and page count are numeric values
        //  This is required to ensure that the page shift can be tracked during generation
        if (!is_numeric($start) || !is_numeric($pages)) {
            throw new Exception('Start page and page count must be numeric values when adding a constituent PDF');
        }

        //  Add the constituent PDF details using the insertion point as a key
        //  The entries will be processed in order on generation
        if (!array_key_exists($insertAfter, $this->constituentPdfs)) {
            $this->constituentPdfs[$insertAfter] = [];
        }

        $this->constituentPdfs[$insertAfter][] = [
            'pdf'   => $pdf,
            'start' => $start,
            'pages' => $pages,
        ];
    }

    /**
     * Insert a single page of the constituent PDF after a specified page
     *
     * @param AbstractPdf|string $pdf
     * @param $pageNumber
     * @param $insertAfter
     */
    protected function addConstituentPdfPage($pdf, $pageNumber, $insertAfter)
    {
        $this->addConstituentPdf($pdf, $pageNumber, 1, $insertAfter);
    }

    /**
     * Insert a static PDF after a specified page
     *
     * @param $pdfFileName
     * @param $start
     * @param $pages
     * @param $insertAfter
     */
    protected function insertStaticPDF($pdfFileName, $start, $pages, $insertAfter)
    {
        $pdfPath = $this->getTemplatePdfFilePath($pdfFileName);
        $this->addConstituentPdf($pdfPath, $start, $pages, $insertAfter);
    }

    /**
     * Insert a blank page (static PDF) after a specified page
     *
     * @param string $insertAfter
     */
    protected function insertBlankPage($insertAfter)
    {
        $this->insertStaticPDF('blank.pdf', 1, 1, $insertAfter);
    }
}
