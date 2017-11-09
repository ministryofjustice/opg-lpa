<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;
use mikehaertl\pdftk\Pdf as PdftkPdf;
use Exception;

/**
 * Class AbstractPdf
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractPdf extends PdftkPdf
{
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
     * Unique file name (with path) for the PDF being created
     *
     * @var
     */
    protected $pdfFile;

    /**
     * Constructor can be triggered with or without an LPA object
     * If an LPA object is passed then the PDF object will execute the create function to populate the data
     *
     * @param Lpa|null $lpa
     * @param null $templateFileName
     * @param array $options
     * @throws Exception
     */
    public function __construct(Lpa $lpa = null, $templateFileName = null, array $options = [])
    {
        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();

        //  Determine the PDF template file to use and, if applicable, check it exists
        $templateFile = null;

        if (!is_null($templateFileName)) {
            $templateFile = $this->config['service']['assets']['template_path_on_ram_disk'] . '/' . $templateFileName;

            if (!file_exists($templateFile)) {
                throw new Exception('The requested PDF template file ' . $templateFile . ' does not exist');
            }
        }

        //  Trigger the parent constructor for any additional set up
        parent::__construct($templateFile, $options);

        //  Build up a PDF file name to use
        $className = array_pop(explode('\\', get_class($this)));
        $pdfFileName =  sprintf('%s-%s.pdf', $className, microtime(true));

        //  If an LPA has been passed then set up the PDF object and trigger the create
        if ($lpa instanceof Lpa) {
            //  Prefix the PDF file name with the LPA reference
            $pdfFileName = str_replace(' ', '-', Formatter::id($lpa->id)) . '-' . $pdfFileName;

            //  Log a message for this PDF creation
            $this->logger->info('Creating ' . $pdfFileName, [
                'lpaId' => $lpa->id
            ]);

            //  Trigger the create now - this will trigger in the child class
            $this->create($lpa);
        }

        //  Set the full file path for this PDF
        $this->pdfFile = $this->config['service']['assets']['intermediate_file_path'] . '/' . $pdfFileName;
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
        //  If required re-get the PDF and set the password - this is actually shrinks the PDF again
        if ($protect) {
            $pdfToProtect = new PdftkPdf($this->pdfFile);
            $pdfToProtect->allow('Printing CopyContents')
                         ->setPassword($this->config['pdf']['password'])
                         ->saveAs($this->pdfFile);
        }

        return $this->pdfFile;
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
