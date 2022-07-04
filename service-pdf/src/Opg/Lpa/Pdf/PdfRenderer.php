<?php

namespace Opg\Lpa\Pdf;

use MakeLogger\Logging\SimpleLoggerTrait;
use Opg\Lpa\Pdf\Lp1f;
use Opg\Lpa\Pdf\Lp1h;
use Opg\Lpa\Pdf\Lpa120;
use Opg\Lpa\Pdf\Aggregator\Lp3;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Exception;
use UnexpectedValueException;
use copy;
use file_exists;
use glob;
use mkdir;
use pathinfo;

class PdfRenderer
{
    use SimpleLoggerTrait;

    /** @var bool */
    private bool $inited = false;

    /**
     * Constructor
     *
     * @param array $assetsConfig See init() for description
     */
    public function __construct(array $assetsConfig)
    {
        $this->init($assetsConfig);
    }

    /**
     * Copy PDF templates from source path to target path.
     *
     * @param array $assetsConfig Array containing two keys:
     *     source_template_path - Source path of PDF templates
     *     template_path_on_ram_disk - Destination path for PDF templates
     * The file names required to render the individual PDFs are stored
     * in the Opg\Lpa\Pdf\Lp*.php classes.
     * @returns bool True if successfully inited, false otherwise.
     */
    public function init(array $assetsConfig)
    {
        if ($this->inited) {
            return true;
        }

        if (!isset($assetsConfig['source_template_path'])) {
            $this->getLogger()->err('source_template_path not set in config');
            return false;
        }

        if (!isset($assetsConfig['template_path_on_ram_disk'])) {
            $this->getLogger()->err('template_path_on_ram_disk not set in config');
            return false;
        }

        // Copy LPA PDF template files into ram disk if they are not found
        $templatePathOnDisk = $assetsConfig['template_path_on_ram_disk'];

        if (!file_exists($templatePathOnDisk)) {
            $this->getLogger()->info('Making template path on RAM disk', [
                'path' => $templatePathOnDisk,
            ]);

            mkdir($templatePathOnDisk, 0777, true);
        }

        foreach (glob($assetsConfig['source_template_path'] . '/*.pdf') as $pdfSource) {
            $pathInfo = pathinfo($pdfSource);

            if (!file_exists($templatePathOnDisk . '/' . $pathInfo['basename'])) {
                $dest = $templatePathOnDisk . '/' . $pathInfo['basename'];

                $this->getLogger()->info('Copying PDF source to RAM disk', [
                    'destination' => $dest,
                ]);

                copy($pdfSource, $dest);
            }
        }

        $this->inited = true;

        return true;
    }

    /**
     * Generate a PDF from an LPA type and data.
     *
     * Note that the template file for the LPA is set up as part of
     * the AbstractWorker constructor. If you are using this class directly
     * to generate PDFs, you will need to copy the required PDF templates
     * to the correct locations (as specified for the PDF classes you're using)
     * before you start.
     *
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string $lpaData JSON document representing the LPA document.
     * @return string Path to the generated PDF file, or null on error
     * @throws Exception
     */
    public function render($docId, $type, $lpaData)
    {
        // Initialise the log message params
        $message = 'PDF successfully generated';
        $isError = false;

        // Define the data that will be used in the logging messages
        $loggingParams = [
            'docId' => $docId,
            'type'  => $type
        ];

        try {
            if (is_array($lpaData) && isset($lpaData['id'])) {
                $lpaId = $lpaData['id'];
            } else {
                $lpaDecode = json_decode($lpaData);

                if (is_null($lpaDecode) || !property_exists($lpaDecode, 'id')) {
                    throw new Exception(
                        'Missing field: id in JSON for docId: ' . $docId .
                        ' This can be caused by incorrectly configured encryption keys or JSON.'
                    );
                }

                $lpaId = $lpaDecode->id;
            }

            // Create the LPA data model and validate it
            $lpa = new Lpa($lpaData);

            if ($lpa->validate()->hasErrors()) {
                throw new Exception('LPA failed validation');
            }

            // Generate the required PDF
            if ($type == 'LP1' && $lpa->document->type == Document::LPA_TYPE_PF) {
                $pdf = new Lp1f($lpa);
            } elseif ($type == 'LP1' && $lpa->document->type == Document::LPA_TYPE_HW) {
                $pdf = new Lp1h($lpa);
            } elseif ($type == 'LP3') {
                $pdf = new Lp3($lpa);
            } elseif ($type == 'LPA120') {
                $pdf = new Lpa120($lpa);
            } else {
                throw new UnexpectedValueException('Invalid form type: ' . $type);
            }

            $pdfFilePath = $pdf->generate(true);

            // Define the data that will be used in the logging messages
            $loggingParams = array_merge($loggingParams, [
                'filePath' => $pdfFilePath,
                'lpaId' => $lpaId,
            ]);

            $pdfSizeK = filesize($pdfFilePath) / 1024;

            $this->getLogger()->debug(
                '----------------- Generated PDF for LPA ' .
                $lpaId . ' at path ' . $pdfFilePath .
                ' (PDF size Kb = ' . $pdfSizeK . ')'
            );
        } catch (Exception $e) {
            $pdfFilePath = null;
            $isError = true;
            $message = 'PDF generation failed with exception: ' . $e->getMessage();
        }

        // Pre-append the Doc ID to the message and log it
        $message = $docId . ': ' . $message;

        if ($isError) {
            $this->getLogger()->err($message, $loggingParams);
        } else {
            $this->getLogger()->info($message, $loggingParams);
        }

        return $pdfFilePath;
    }
}
