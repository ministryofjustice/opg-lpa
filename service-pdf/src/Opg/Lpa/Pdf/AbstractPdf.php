<?php

namespace Opg\Lpa\Pdf;

use mikehaertl\pdftk\Pdf as PdftkPdf;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;
use Exception;

/**
 * Class AbstractPdf
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractPdf extends PdftkPdf
{
    /**
     * Constants
     */
    const CHECK_BOX_ON = 'On';

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
     * PDF template file name (without path) for this PDF object - value to be set in extending class
     *
     * @var
     */
    protected $templateFileName;

    /**
     * Unique file name (with path) for the PDF being created
     *
     * @var
     */
    private $pdfFile;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    protected $leadingNewLineFields = [];

    /**
     * @param Lpa $lpa
     * @throws Exception
     */
    public function __construct(Lpa $lpa)
    {
        //  Confirm that the LPA provided can be used to generate this type of PDF
        $stateChecker = new StateChecker($lpa);

        //  If applicable check that the document can be created
        if ($this instanceof Lpa120 && !$stateChecker->canGenerateLPA120()) {
            throw new Exception('LPA does not contain all the required data to generate ' . get_class($this));
        }

        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();

        //  Determine the PDF template file to use and check it exists
        $templateFile = $this->config['service']['assets']['template_path_on_ram_disk'] . '/' . $this->templateFileName;

        //  Check that the PDF template exists
        if (!file_exists($templateFile)) {
            throw new Exception('The requested PDF template file ' . $templateFile .  ' does not exist');
        }

        //  Trigger the parent constructor to set up the PDF with the template file
        parent::__construct($templateFile);

        //  Generate the unique file name for this PDF and set with the full path
        $fileType = array_pop(explode('\\', get_class($this)));
        $lpaReference = str_replace(' ', '-', Formatter::id($lpa->id));

        $pdfFileName = sprintf('%s-%s-%s.pdf', strtoupper($fileType), $lpaReference, floor(microtime(true)));

        //  Log a message for this PDF creation
        $this->logger->info('Creating ' . $pdfFileName, [
            'lpaId' => $lpa->id
        ]);

        //  Set the full file path for this PDF
        $this->pdfFile = $this->config['service']['assets']['intermediate_file_path'] . '/' . $pdfFileName;

        //  Trigger the create now - this will trigger in the child class
        $this->create($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    abstract protected function create(Lpa $lpa);

    /**
     * Generate the PDF - this will save a copy to the file system
     *
     * @param bool $protect
     * @return string
     */
    public function generate($protect = false)
    {
        $this->fillForm($this->data)
             ->flatten()
             ->saveAs($this->pdfFile);

        //  If required re-get the PDF and set the password - this is actually shrinks the PDF again
        //  TODO - Investigate why this is the case!
        if ($protect) {
            $pdf = new PdftkPdf($this->pdfFile);
            $pdf->allow('Printing CopyContents')
                ->setPassword($this->config['pdf']['password'])
                ->saveAs($this->pdfFile);
        }

        return $this->pdfFile;
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
     * Clean up any created files when we're finished
     */
    public function __destruct()
    {
        if (file_exists($this->pdfFile)) {
            unlink($this->pdfFile);
        }
    }
}
