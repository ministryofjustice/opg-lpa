<?php

namespace Opg\Lpa\Pdf;

use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Aggregator\Lp3;
use Exception;
use Opg\Lpa\Pdf\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use UnexpectedValueException;

class PdfRenderer implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var bool */
    private bool $inited = false;

    /** @var PdftkFactory */
    private PdftkFactory $pdftkFactory;

    /**
     * Constructor
     *
     * @param Config $config Must have this structure:
     *
     * [
     *     'service' => [
     *         'assets' => [
     *             'template_path' => 'destination dir for PDF templates',
     *             'intermediate_file_path' => 'destination dir for generated PDFs'
     *         ],
     *     ]
     * ]
     *
     * Note that template_path
     * is used to set up the templates for PDF generation, while the other
     * variables are used by PDF class generate() methods.
     *
     * @param ?PdftkFactory $pdftkFactory
     */
    public function __construct(Config $config, ?PdftkFactory $pdftkFactory = null)
    {
        $this->init($config['service']['assets']);

        if (is_null($pdftkFactory)) {
            $pdftkFactory = new PdftkFactory();
        }
        $this->pdftkFactory = $pdftkFactory;
    }

    /**
     * Copy PDF templates from source path to target path.
     *
     * @param array $assetsConfig Array containing two keys:
     *     template_path - Location path for PDF templates
     *     intermediate_file_path - cache location for generated pdfs
     * The file names required to render the individual PDFs are stored
     * in the Opg\Lpa\Pdf\Lp*.php classes.
     */
    public function init(array $assetsConfig): void
    {
        if ($this->inited) {
            return;
        }

        if (!isset($assetsConfig['template_path'])) {
            $this->getLogger()->error('template_path not set in config');
            return;
        }

        if (!isset($assetsConfig['intermediate_file_path'])) {
            $this->getLogger()->error('intermediate_file_path not set in config');
            return;
        }

<<<<<<< HEAD
        //create folder for pdf_cache
        if (!is_dir($assetsConfig['intermediate_file_path'])) {
            mkdir($assetsConfig['intermediate_file_path']);
=======
        // Copy LPA PDF template files into ram disk if they are not found
        $templatePathOnDisk = $assetsConfig['template_path_on_ram_disk'];

        if (!file_exists($templatePathOnDisk)) {
            $this->getLogger()->info('Making template path on RAM disk', [
                'path' => $templatePathOnDisk,
            ]);

            // mkdir($templatePathOnDisk, 0777, true);
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
>>>>>>> 1168e234f (remove read-only write)
        }

        $this->inited = true;
    }

    /**
     * Generate a PDF from an LPA type and data.
     *
     * Note that the template files for each LPA are set up as part of
     * the constructor. Also note that the config passed to the constructor
     * is in turn passed to each PDF to set its destination path and the password
     * used to protect it (if required).
     *
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string $lpaData JSON document representing the LPA document.
     * @return array with format
     * [
     *     'lpaId' => <ID of the LPA>,
     *     'docId' => <document ID passed into this function>,
     *     'filepath' => <path to PDF file>,
     *     'content' => <content of PDF>,
     * ]
     * @throws Exception
     */
    public function render($docId, $type, $lpaData)
    {
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
                $pdf = new Lp1f($lpa, [], $this->pdftkFactory);
            } elseif ($type == 'LP1' && $lpa->document->type == Document::LPA_TYPE_HW) {
                $pdf = new Lp1h($lpa, [], $this->pdftkFactory);
            } elseif ($type == 'LP3') {
                $pdf = new Lp3($lpa, null, [], $this->pdftkFactory);
            } elseif ($type == 'LPA120') {
                $pdf = new Lpa120($lpa, [], $this->pdftkFactory);
            } else {
                throw new UnexpectedValueException('Invalid form type: ' . $type);
            }

            $pdfFilePath = $pdf->generate(true);
            $pdfContent = file_get_contents($pdfFilePath);
            $pdfSizeK = filesize($pdfFilePath) / 1024;

            $this->getLogger()->debug('Generated PDF for LPA', [
                'lpaId' => $lpaId,
                'docId' => $docId,
                'type'  => $type,
                'filePath' => $pdfFilePath,
                'pdfSize' => $pdfSizeK
            ]);
        } catch (Exception $e) {
            $pdfFilePath = null;
            $pdfContent = null;
            $this->getLogger()->error('PDF generation failed', [
                'docId' => $docId,
                'type'  => $type,
                'error_code' => 'PDF_GENERATION_FAILED',
                'exception' => $e->getMessage(),
                'status' => $e->getCode()
            ]);
        }

        return [
            'lpaId' => $lpaId,
            'docId' => $docId,
            'filepath' => $pdfFilePath,
            'content' => $pdfContent,
        ];
    }
}
