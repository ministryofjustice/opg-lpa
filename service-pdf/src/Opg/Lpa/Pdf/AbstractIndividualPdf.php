<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use mikehaertl\pdftk\Pdf as PdftkPdf;
use ZendPdf\PdfDocument as ZendPdfDocument;
use Exception;

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
     * @var array
     */
    protected $leadingNewLineFields = [];

    /**
     * Area references that should have a strike through
     *
     * @var array
     */
    private $strikeThroughTargets = [];

    /**
     * @var array
     */
    private $constituentPdfs = [];

    /**
     * Constructor can be triggered with or without an LPA object
     * If an LPA object is passed then the PDF object will execute the create function to populate the data
     *
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
            $stateChecker = new StateChecker($lpa);

            //  If applicable check that the document can be created
            if (($this instanceof Lp3 && !$stateChecker->canGenerateLP3())
                || ($this instanceof Lpa120 && !$stateChecker->canGenerateLPA120())) {

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
     * @return $this
     */
    protected function setData($key, $value)
    {
        //  If applicable insert a new line char
        if (in_array($key, $this->leadingNewLineFields)) {
            $value = "\n" . $value;
        }

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Return the footer content from the config
     *
     * @param $type
     * @return mixed
     */
    protected function getFooter($type)
    {
        return $this->config['footer'][$type];
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

        //  Draw any strike throughs
        if (!empty($this->strikeThroughTargets)) {
            //  Check to see if drawing cross lines is disabled or not
            $disableStrikeThroughLines = false;

            if (isset($this->config['service']['disable_strike_through_lines'])) {
                $disableStrikeThroughLines = (bool)$this->config['service']['disable_strike_through_lines'];
            }

            if (!$disableStrikeThroughLines) {
                // draw cross lines
                $pdfForStrikethroughs = ZendPdfDocument::load($this->pdfFile);

                foreach ($this->strikeThroughTargets as $pageNo => $pageDrawingTargets) {
                    $page = $pdfForStrikethroughs->pages[$pageNo]->setLineWidth(10);

                    foreach ($pageDrawingTargets as $pageDrawingTarget) {
                        //  Get the coordinates for this target from the config
                        if (isset($this->config['strike_throughs'][$pageDrawingTarget])) {
                            $targetStrikeThroughCoordinates = $this->config['strike_throughs'][$pageDrawingTarget];

                            $page->drawLine(
                                $targetStrikeThroughCoordinates['bx'],
                                $targetStrikeThroughCoordinates['by'],
                                $targetStrikeThroughCoordinates['tx'],
                                $targetStrikeThroughCoordinates['ty']
                            );
                        }
                    }
                }

                $pdfForStrikethroughs->save($this->pdfFile);
            }
        }

        //  Process any constituent PDFs
        if (!empty($this->constituentPdfs)) {
            //  Loop through the constituent PDF settings and gradually adapt the document
            foreach ($this->constituentPdfs as $constituentPdfData) {
                //  Execute the generation for this constituent
                $constituentPdfFile = $constituentPdfData['pdf'];

                //  If this PDF is an abstract PDF then trigger the generate and get the path
                if ($constituentPdfFile instanceof AbstractIndividualPdf) {
                    $constituentPdfFile = $constituentPdfFile->generate();
                }

                $pdfMaster = new PdftkPdf([
                    'A' => $this->pdfFile,
                    'B' => $constituentPdfFile,
                ]);

                $insertAt = $constituentPdfData['pos'];
                $startAt = $constituentPdfData['start'];
                $endAt = (is_numeric($constituentPdfData['pages']) ? $startAt + $constituentPdfData['pages'] - 1 : $constituentPdfData['pages']);

                if ($insertAt == 'end') {
                    //  Add the constituent pages to the end
                    $pdfMaster->cat(1, 'end', 'A')
                              ->cat($startAt, $endAt, 'B');
                } else {
                    //  Insert the constituent pages in the middle
                    $pdfMaster->cat(1, $insertAt - 1, 'A')
                              ->cat($startAt, $endAt, 'B')
                              ->cat($insertAt, 'end', 'A');
                }

                $pdfMaster->saveAs($this->pdfFile);
            }
        }

        //  Trigger the parent
        return parent::generate($protect);
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
        //  Adjust the page number for zero based indexes
        $pageNumber--;

        //  If a section doesn't exist for this page create one now
        if (!isset($this->strikeThroughTargets[$pageNumber])) {
            $this->strikeThroughTargets[$pageNumber] = [];
        }

        $this->strikeThroughTargets[$pageNumber][] = $areaReference;

        return $this;
    }

    /**
     * Insert a number of pages of the constituent PDF at a set position (page)
     *
     * @param AbstractIndividualPdf $pdf
     * @param $start
     * @param $pages
     * @param $position
     */
    protected function addConstituentPdf(AbstractIndividualPdf $pdf, $start, $pages, $position)
    {
        $this->constituentPdfs[] = [
            'pdf'   => $pdf,
            'start' => $start,
            'pages' => $pages,
            'pos'   => $position,
        ];
    }

    /**
     * Insert a single page of the constituent PDF at a set position (page)
     *
     * @param AbstractIndividualPdf $pdf
     * @param $pageNumber
     * @param $position
     */
    protected function addConstituentPdfPage(AbstractIndividualPdf $pdf, $pageNumber, $position)
    {
        $this->addConstituentPdf($pdf, $pageNumber, 1, $position);
    }

    /**
     * Insert a blank page as a constituent PDF
     *
     * @param string $position
     */
    protected function insertBlankPage($position = 'end')
    {
        $this->constituentPdfs[] = [
            'pdf'   => $this->config['service']['assets']['template_path_on_ram_disk'] . '/blank.pdf',
            'start' => 1,
            'pages' => 1,
            'pos'   => $position,
        ];
    }
}
