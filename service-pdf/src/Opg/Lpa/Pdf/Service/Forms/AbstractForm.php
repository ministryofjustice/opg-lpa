<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;
use ZendPdf\PdfDocument as ZendPdfDocument;
use mikehaertl\pdftk\Pdf;
use Exception;

abstract class AbstractForm
{
    const CONTENT_TYPE_PREFERENCES = 'preferences';
    const CONTENT_TYPE_INSTRUCTIONS = 'instructions';

    const MAX_ATTORNEYS_ON_STANDARD_FORM = 4;
    const MAX_REPLACEMENT_ATTORNEYS_ON_STANDARD_FORM = 2;
    const MAX_PEOPLE_TO_NOTIFY_ON_STANDARD_FORM = 4;
    const MAX_ATTORNEY_SIGNATURE_PAGES_ON_STANDARD_FORM = 4;
    const MAX_ATTORNEY_APPLICANTS_ON_STANDARD_FORM = 4;
    const MAX_ATTORNEY_APPLICANTS_SIGNATURE_ON_STANDARD_FORM = 4;

    const BOX_CHARS_PER_ROW = 84;
    const BOX_NO_OF_ROWS = 6;
    const BOX_NO_OF_ROWS_CS2 = 14;

    /**
     * Logger utility
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Config utility
     *
     * @var Config
     */
    protected $config;

    /**
     *
     * @var LPA model object
     */
    protected $lpa;

    /**
     * @var Pdf
     */
    protected $pdf;

    /**
     * The path of the pdf file to be generated.
     * @var string
     */
    protected $generatedPdfFilePath = null;

    /**
     * Storage path for intermediate pdf files - needed for LP1F/H and LP3.
     * @var array
     */
    protected $interFileStack = [];

    /**
     * The folder that stores template PDFs which all form elements values are empty.
     * @var string
     */
    protected $pdfTemplatesPath;

    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile;

    /**
     * Array to hold the areas where we should so a strike through on the PDF pages
     *
     * @var array
     */
    private $strikeThroughTargets = [];

    public function __construct(Lpa $lpa)
    {
        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();

        $this->lpa = $lpa;
        $this->pdfTemplatesPath = $this->config['service']['assets']['template_path_on_ram_disk'];
    }

    abstract protected function generate();

    /**
     * Common function to log the start of generation - if the generated filename is null then the class name will be used to determine it
     *
     * @param string $generatedFilename
     */
    protected function logGenerationStatement($generatedFilename = null)
    {
        //  Determine the filename for the PDF being generated
        if (is_null($generatedFilename)) {
            $generatedFilename = basename(str_replace('\\', '/', get_class($this)));
        }

        $this->logger->info('Generating ' . $generatedFilename, [
            'lpaId' => $this->lpa->id
        ]);
    }

    /**
     * Get the PDF for this form - instantiate it if necessary
     *
     * @param  bool $forceNew
     * @return Pdf
     * @throws Exception
     */
    public function getPdfObject($forceNew = false)
    {
        if ($forceNew === true || is_null($this->pdf)) {
            $pdfTemplateFile = $this->pdfTemplateFile;

            //  If the PDF template file variable is an array then we need to pick a file by LPA type
            if (is_array($pdfTemplateFile)) {
                if (!isset($pdfTemplateFile[$this->lpa->document->type])) {
                    throw new Exception(sprintf('%s PDF template file can not be determined for LPA type %s', get_class($this), $this->lpa->document->type));
                }

                $pdfTemplateFile = $pdfTemplateFile[$this->lpa->document->type];
            }

            $this->pdf = new Pdf($this->getPdfTemplateFilePath($pdfTemplateFile));
        }

        return $this->pdf;
    }

    public function getPdfTemplateFilePath($pdfTemplateFilename)
    {
        return $this->pdfTemplatesPath . '//' . $pdfTemplateFilename;
    }

    /**
     * Get a temp file path to be used for the specified file type right now (in milliseconds)
     * If no file type is passed into the constructor then derive it from the constructor
     *
     * @param string $fileType
     * @return string
     */
    protected function getTmpFilePath($fileType = null)
    {
        if (is_null($fileType)) {
            $fileType = explode('\\', get_class($this));
            $fileType = strtoupper(array_pop($fileType));

            //  If this is a top level PDF form then prefix with 'PDF-'
            if ($this instanceof AbstractTopForm) {
                $fileType = 'PDF-' . $fileType;
            }
        }

        $lpaFileRef = Formatter::id($this->lpa->id) . '-' . microtime(true);

        $filename = $fileType . '-' . str_replace([' ', '.'], '-', $lpaFileRef) . '.pdf';

        return $this->config['service']['assets']['intermediate_file_path'] . '/' . $filename;
    }

    /**
     * Register a temp file in $interFileStack
     *
     * @param $fileType
     * @return string
     */
    public function registerTempFile($fileType)
    {
        if (!isset($this->interFileStack[$fileType])) {
            $this->interFileStack[$fileType] = [];
        }

        $path = $this->getTmpFilePath($fileType);
        $this->interFileStack[$fileType][] = $path;

        return $path;
    }

    /**
     * Add a strike through line to the specified page
     *
     * @param $areaReference
     * @param int $pageNumber
     * @return $this
     */
    protected function addStrikeThrough($areaReference, $pageNumber = 0)
    {
        //  If a section doesn't exist for this page create one now
        if (!isset($this->strikeThroughTargets[$pageNumber])) {
            $this->strikeThroughTargets[$pageNumber] = [];
        }

        $this->strikeThroughTargets[$pageNumber][] = $areaReference;

        return $this;
    }

    /**
     * Draw strike through lines if any have been set
     *
     * @param string $filePath
     * @codeCoverageIgnore
     */
    protected function drawStrikeThroughs($filePath)
    {
        if (!empty($this->strikeThroughTargets)) {
            //  Check to see if drawing cross lines is disabled or not
            $disableStrikeThroughLines = false;

            if (isset($this->config['service']['disable_strike_through_lines'])) {
                $disableStrikeThroughLines = (bool)$this->config['service']['disable_strike_through_lines'];
            }

            if (!$disableStrikeThroughLines) {
                // draw cross lines
                $pdf = ZendPdfDocument::load($filePath);

                foreach ($this->strikeThroughTargets as $pageNo => $pageDrawingTargets) {
                    $page = $pdf->pages[$pageNo]->setLineWidth(10);

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

                $pdf->save($filePath);
            }
        }
    }

    /**
     * Convert all new lines with spaces to fill out to the end of each line
     *
     * @param string $contentIn
     * @return string
     */
    protected function flattenTextContent($contentIn)
    {
        $content = '';

        foreach (explode("\r\n", trim($contentIn)) as $contentLine) {
            $content .= wordwrap($contentLine, self::BOX_CHARS_PER_ROW, "\r\n", false);
            $content .= "\r\n";
        }

        $paragraphs = explode("\r\n", $content);

        for ($i = 0; $i < count($paragraphs); $i++) {
            $paragraphs[$i] = trim($paragraphs[$i]);

            if (strlen($paragraphs[$i]) == 0) {
                unset($paragraphs[$i]);
            } else {
                // calculate how many space chars to be appended to replace the new line in this paragraph.
                if (strlen($paragraphs[$i]) % self::BOX_CHARS_PER_ROW) {
                    $noOfSpaces = self::BOX_CHARS_PER_ROW - strlen($paragraphs[$i]) % self::BOX_CHARS_PER_ROW;
                    if ($noOfSpaces > 0) {
                        $paragraphs[$i] .= str_repeat(" ", $noOfSpaces);
                    }
                }
            }
        }

        return implode("\r\n", $paragraphs);
    }

    /**
     * Get content for a multiline text box.
     *
     * @param int $pageNo
     * @param string $content - user input content for preference/instruction/decisions/step-in
     * @return string|null
     */
    protected function getInstructionsAndPreferencesContent($pageNo, $content)
    {
        $flattenContent = $this->flattenTextContent($content);

        if ($pageNo == 0) {
            return "\r\n" . substr($flattenContent, 0, (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS);
        } else {
            $chunks = str_split(substr($flattenContent, (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS), (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2);
            if (isset($chunks[$pageNo - 1])) {
                return "\r\n" . $chunks[$pageNo - 1];
            } else {
                return null;
            }
        }
    }

    /**
     * if there is a trust corp, make it the first item in the attorneys array.
     *
     * @param string $attorneyType - 'primaryAttorneys'|'replacementAttorneys'
     * @return array of primaryAttorneys or replacementAttorneys
     */
    protected function sortAttorneys($attorneyType)
    {
        $sortedAttorneys = [];
        $trustAttorney = null;

        foreach ($this->lpa->document->$attorneyType as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $trustAttorney = $attorney;
            } else {
                $sortedAttorneys[] = $attorney;
            }
        }

        if (!is_null($trustAttorney)) {
            array_unshift($sortedAttorneys, $trustAttorney);
        }

        return $sortedAttorneys;
    }

    /**
     * Get the trust corporation from the LPA if one exists - return null if not
     *
     * @return TrustCorporation|null
     */
    protected function getTrustCorporation()
    {
        $attorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);

        //  Loop through the attorneys to try to find the trust attorney
        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                return $attorney;
            }
        }

        return null;
    }

    /**
     * Convenience method to get the form type suffix - used by multiple parts of the code
     *
     * @return string
     */
    protected function getFormTypeSuffix()
    {
        return ($this->lpa->document->type == Document::LPA_TYPE_PF ? 'pf' : 'hw');
    }
}
