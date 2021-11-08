<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Logger\Logger;
use Opg\Lpa\Pdf\PdftkFactory;
use mikehaertl\pdftk\Pdf as PdftkPdf;
use Exception;
use JsonSerializable;

/**
 * Class AbstractPdf
 * @package Opg\Lpa\Pdf
 */
abstract class AbstractPdf extends PdftkPdf implements JsonSerializable
{
    /**
     * Constants
     */
    const MAX_ATTORNEYS_SECTION_2 = 4;
    const MAX_REPLACEMENT_ATTORNEYS_SECTION_4 = 2;
    const MAX_PEOPLE_TO_NOTIFY_SECTION_6 = 4;

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
     * @var string
     */
    protected string $pdfFile;

    /**
     * @var int
     */
    protected int $numberOfPages;

    /**
     * Formatted LPA reference in the format ANNN-NNNN-NNNN
     *
     * @var string
     */
    protected string $formattedLpaRef;

    /**
     * Factory for creating mikehaertl\pdftk\Pdf instances
     * @var PdftkFactory
     */
    protected PdftkFactory $pdftkFactory;

    /**
     * Constructor can be triggered with or without an LPA object
     * If an LPA object is passed then the PDF object will execute the create function to populate the data
     *
     * @param Lpa|null $lpa
     * @param null $templateFileName
     * @param array $options
     * @param ?PdftkFactory $pdftkFactory
     * @throws Exception
     */
    public function __construct(Lpa $lpa = null, $templateFileName = null, array $options = [], ?PdftkFactory $pdftkFactory = null)
    {
        if (is_null($pdftkFactory)) {
            $pdftkFactory = new PdftkFactory();
        }
        $this->pdftkFactory = $pdftkFactory;
        $this->formattedLpaRef = '';

        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();

        //  Determine the PDF template file to use and, if applicable, check it exists
        $templateFile = null;

        if (!is_null($templateFileName)) {
            $templateFile = $this->getTemplatePdfFilePath($templateFileName);

            if (!file_exists($templateFile)) {
                throw new Exception('The requested PDF template file ' . $templateFile . ' does not exist');
            }

            //  Determine the number of pages for the PDF template using the suggest method in...
            //  https://github.com/mikehaertl/php-pdftk/issues/56
            //  Create a new copy of the PDF for this so as not to trigger the command finally
            $pageCountPdf = $this->pdftkFactory->create($templateFile);

            if (preg_match('/NumberOfPages: (\d+)/', $pageCountPdf->getData(), $m)) {
                $this->numberOfPages = $m[1];
            }
        }

        // Pass the pdftk command configured for the PdftkFactory to the parent
        // constructor, so that the parent uses the same pdftk command
        $options['command'] = $this->pdftkFactory->getPdftkCommand();

        // Trigger the parent constructor for any additional set up
        parent::__construct($templateFile, $options);

        //  Build up a PDF file name to use
        $pdfClassArr = explode('\\', get_class($this));
        $pdfFileName =  array_pop($pdfClassArr) . '.pdf';

        //  If an LPA has been passed then set up the PDF object and trigger the create
        if ($lpa instanceof Lpa) {
            //  Set the formatted LPA ref for use later
            $this->formattedLpaRef = Formatter::id($lpa->getId());

            //  Log a message for this PDF creation
            $this->logger->info('Creating ' . $pdfFileName . ' for ' . $this->formattedLpaRef, [
                'lpaId' => $lpa->getId()
            ]);

            //  Trigger the create now - this will trigger in the child class
            $this->create($lpa);
        }

        //  Set the full file path for this PDF
        $this->pdfFile = $this->getIntermediatePdfFilePath($pdfFileName);
    }

    /**
     * @param string $templatePdfFileName
     * @return string
     */
    protected function getTemplatePdfFilePath(string $templatePdfFileName): string
    {
        return $this->config['service']['assets']['template_path_on_ram_disk'] . '/' . $templatePdfFileName;
    }

    /**
     * Get a unique intermediary file name and path - a micro timestamp will be used here to ensure uniqueness
     *
     * @param string $intermediatePdfFileName
     * @return string
     */
    protected function getIntermediatePdfFilePath(string $intermediatePdfFileName): string
    {
        //  Create a (near) unique intermediate file name using the formatted LPA ref (if set) and a micro timestamp
        if (!is_null($this->formattedLpaRef)) {
            $intermediatePdfFileName = str_replace(' ', '-', $this->formattedLpaRef) . '-' . $intermediatePdfFileName;
        }

        return sprintf('%s/%s-%s', $this->config['service']['assets']['intermediate_file_path'], microtime(true), $intermediatePdfFileName);
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
    public function generate(bool $protect = false): string
    {
        //  If required re-get the PDF and set the password
        if ($protect) {
            $this->protectPdf();
        }

        return $this->pdfFile;
    }

    /**
     * Protect the PDF with the password
     */
    protected function protectPdf(): void
    {
        $pdfToProtect = $this->pdftkFactory->create($this->pdfFile);

        $pdfToProtect->allow('Printing CopyContents')
                     ->setPassword($this->config['pdf']['password'])
                     ->saveAs($this->pdfFile);
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

    /**
     * JsonSerializable implementation
     *
     * @return get-class-of<$this, Opg\Lpa\Pdf\AbstractPdf&static>[]
     *
     * @psalm-return array{class: get-class-of<$this, Opg\Lpa\Pdf\AbstractPdf&static>}
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => get_class($this),
        ];
    }
}
