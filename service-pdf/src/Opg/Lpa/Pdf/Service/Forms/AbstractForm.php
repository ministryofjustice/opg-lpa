<?php

namespace Opg\Lpa\Pdf\Service\Forms;

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
    const CONTENT_TYPE_ATTORNEY_DECISIONS = 'decisions';
    const CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN = 'how-replacement-attorneys-step-in';
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
     * Data to be populated into PDF form elements.
     *
     * @var array
     */
    protected $dataForForm = [];

    /**
     * The path of the pdf file to be generated.
     * @var string
     */
    protected $generatedPdfFilePath = null;

    /**
     * Storage path for intermediate pdf files - needed for LP1F/H and LP3.
     * @var array
     */
    protected $interFileStack = array();

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
     * bx - bottom x
     * by - bottom y
     * tx - top x
     * ty - top y
     * @var array - cross lines corrrdinates
     */
    protected $crossLineParams = array(
        'primaryAttorney-1-hw' => array('bx' => 313, 'by' => 243, 'tx' => 550, 'ty' => 546),
        'primaryAttorney-1-pf' => array('bx' => 313, 'by' => 178, 'tx' => 550, 'ty' => 471),
        'primaryAttorney-2' => array('bx' => 45, 'by' => 375, 'tx' => 282, 'ty' => 679),
        'primaryAttorney-3' => array('bx' => 313, 'by' => 375, 'tx' => 550, 'ty' => 679),
        'replacementAttorney-0-hw' => array('bx' => 45, 'by' => 317, 'tx' => 283, 'ty' => 538),
        'replacementAttorney-1-hw' => array('bx' => 313, 'by' => 317, 'tx' => 551, 'ty' => 538),
        'replacementAttorney-0-pf' => array('bx' => 45, 'by' => 308, 'tx' => 283, 'ty' => 530),
        'replacementAttorney-1-pf' => array('bx' => 313, 'by' => 308, 'tx' => 551, 'ty' => 530),
        'life-sustain-A' => array('bx' => 44, 'by' => 265, 'tx' => 283, 'ty' => 478),
        'life-sustain-B' => array('bx' => 307, 'by' => 265, 'tx' => 550, 'ty' => 478),
        'people-to-notify-0' => array('bx' => 44, 'by' => 335, 'tx' => 283, 'ty' => 501),
        'people-to-notify-1' => array('bx' => 312, 'by' => 335, 'tx' => 552, 'ty' => 501),
        'people-to-notify-2' => array('bx' => 44, 'by' => 127, 'tx' => 283, 'ty' => 294),
        'people-to-notify-3' => array('bx' => 312, 'by' => 127, 'tx' => 552, 'ty' => 294),
        'preference' => array('bx' => 41, 'by' => 423, 'tx' => 554, 'ty' => 532),
        'instruction' => array('bx' => 41, 'by' => 122, 'tx' => 554, 'ty' => 231),
        'attorney-signature-hw' => array('bx' => 42, 'by' => 143, 'tx' => 553, 'ty' => 317),
        'attorney-signature-pf' => array('bx' => 42, 'by' => 131, 'tx' => 553, 'ty' => 306),
        'applicant-0-hw' => array('bx' => 42, 'by' => 315, 'tx' => 283, 'ty' => 413),
        'applicant-1-hw' => array('bx' => 308, 'by' => 315, 'tx' => 549, 'ty' => 413),
        'applicant-2-hw' => array('bx' => 42, 'by' => 147, 'tx' => 283, 'ty' => 245),
        'applicant-3-hw' => array('bx' => 308, 'by' => 147, 'tx' => 549, 'ty' => 245),
        'applicant-0-pf' => array('bx' => 42, 'by' => 319, 'tx' => 283, 'ty' => 417),
        'applicant-1-pf' => array('bx' => 308, 'by' => 319, 'tx' => 549, 'ty' => 417),
        'applicant-2-pf' => array('bx' => 42, 'by' => 155, 'tx' => 283, 'ty' => 253),
        'applicant-3-pf' => array('bx' => 308, 'by' => 155, 'tx' => 549, 'ty' => 253),
        'applicant-signature-1' => array('bx' => 308, 'by' => 395, 'tx' => 549, 'ty' => 493),
        'applicant-signature-2' => array('bx' => 42, 'by' => 262, 'tx' => 283, 'ty' => 360),
        'applicant-signature-3' => array('bx' => 308, 'by' => 262, 'tx' => 549, 'ty' => 360),
        'additional-applicant-1-hw' => array('bx' => 308, 'by' => 315, 'tx' => 549, 'ty' => 413),
        'additional-applicant-2-hw' => array('bx' => 42, 'by' => 147, 'tx' => 283, 'ty' => 245),
        'additional-applicant-3-hw' => array('bx' => 308, 'by' => 147, 'tx' => 549, 'ty' => 245),
        'additional-applicant-1-pf' => array('bx' => 308, 'by' => 319, 'tx' => 549, 'ty' => 417),
        'additional-applicant-2-pf' => array('bx' => 42, 'by' => 155, 'tx' => 283, 'ty' => 253),
        'additional-applicant-3-pf' => array('bx' => 308, 'by' => 155, 'tx' => 549, 'ty' => 253),
        'correspondent-empty-address' => array('bx' => 42, 'by' => 362, 'tx' => 284, 'ty' => 433),
        'correspondent-empty-name-address' => array('bx' => 42, 'by' => 362, 'tx' => 413, 'ty' => 565),
        'cs1' => array('bx' => 313, 'by' => 262, 'tx' => 558, 'ty' => 645),
        'lp3-primaryAttorney-1' => array('bx' => 312, 'by' => 458, 'tx' => 552, 'ty' => 602),
        'lp3-primaryAttorney-2' => array('bx' => 43, 'by' => 242, 'tx' => 283, 'ty' => 386),
        'lp3-primaryAttorney-3' => array('bx' => 312, 'by' => 242, 'tx' => 552, 'ty' => 386)
    );

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
     * Get generated PDF file path
     * @return string
     */
    public function getPdfFilePath()
    {
        return $this->generatedPdfFilePath;
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

    public function getBlankPdfTemplateFilePath()
    {
        return $this->getPdfTemplateFilePath('blank.pdf');
    }

    /**
     * @param string $fileType
     * @return string
     */
    protected function getTmpFilePath($fileType)
    {
        $lpaFileRef = Formatter::id($this->lpa->id) . '-' . microtime(true);

        $filename = $fileType . '-' . str_replace(array(' ', '.'), '-', $lpaFileRef) . '.pdf';

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
     * Draw cross lines
     * @param string $filePath
     * @param array $params [pageNo=>crossLineParamName]
     * @codeCoverageIgnore
     */
    protected function drawCrossLines($filePath, $params)
    {
        //  Check to see if drawing cross lines is disabled or not
        $disableDrawCrossLines = false;

        if (isset($this->config['service']['disable_draw_cross_lines'])) {
            $disableDrawCrossLines = (bool)$this->config['service']['disable_draw_cross_lines'];
        }

        if (!$disableDrawCrossLines) {
            // draw cross lines
            $pdf = ZendPdfDocument::load($filePath);
            foreach ($params as $pageNo => $blockNames) {
                $page = $pdf->pages[$pageNo]->setLineWidth(10);
                foreach ($blockNames as $blockName) {
                    $page->drawLine(
                        $this->crossLineParams[$blockName]['bx'],
                        $this->crossLineParams[$blockName]['by'],
                        $this->crossLineParams[$blockName]['tx'],
                        $this->crossLineParams[$blockName]['ty']
                    );
                }
            } // foreach

            $pdf->save($filePath);
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
     * @param $contentType
     * @return string|null
     */
    protected function getContentForBox($pageNo, $content, $contentType)
    {
        $flattenContent = $this->flattenTextContent($content);

        // return content for preference or instruction in section 7.
        if (($contentType == self::CONTENT_TYPE_INSTRUCTIONS) || ($contentType == self::CONTENT_TYPE_PREFERENCES)) {
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
        } else {
            $chunks = str_split($flattenContent, (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2);
            if (isset($chunks[$pageNo])) {
                return "\r\n" . $chunks[$pageNo];
            } else {
                return null;
            }
        }
    }

    public function cleanup()
    {
        //  TODO - Refactor this...
        if (\file_exists($this->generatedPdfFilePath)) {
            unlink($this->generatedPdfFilePath);
        }

        // remove all generated intermediate pdf files
        foreach ($this->interFileStack as $type => $paths) {
            if (is_string($paths)) {
                if (\file_exists($paths)) {
                    unlink($paths);
                }
            } elseif (is_array($paths)) {
                foreach ($paths as $path) {
                    if (\file_exists($path)) {
                        unlink($path);
                    }
                }
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
        if (count($this->lpa->document->$attorneyType) < 2) {
            return $this->lpa->document->$attorneyType;
        }

        if ($this->hasTrustCorporation($this->lpa->document->$attorneyType)) {
            $attorneys = $this->lpa->document->$attorneyType;
        } else {
            return $this->lpa->document->$attorneyType;
        }

        $sortedAttorneys = [];

        foreach ($attorneys as $idx => $attorney) {
            if ($attorney instanceof TrustCorporation) {
                $trustCorp = $attorney;
            } else {
                $sortedAttorneys[] = $attorney;
            }
        }

        array_unshift($sortedAttorneys, $trustCorp);

        return $sortedAttorneys;
    }

    /**
     * check if there is a trust corp in the whole LPA or in primary attorneys or replacement attorneys.
     *
     * @param  $attorneys
     * @return bool
     */
    protected function hasTrustCorporation($attorneys = null)
    {
        return ($this->getTrustCorporation($attorneys) instanceof TrustCorporation);
    }

    /**
     * Get the trust corporation from the provided array of attorneys, or the LPA, if one exists - return null if not
     *
     * @param  $attorneys
     * @return TrustCorporation|null
     */
    protected function getTrustCorporation($attorneys = null)
    {
        if (is_null($attorneys)) {
            $attorneys = array_merge($this->lpa->document->primaryAttorneys, $this->lpa->document->replacementAttorneys);
        }

        //  Loop through the attorneys to try to find the trust attorney
        foreach ($attorneys as $attorney) {
            if ($attorney instanceof TrustCorporation) {
                return $attorney;
            }
        }

        return null;
    }
}
