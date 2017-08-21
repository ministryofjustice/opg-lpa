<?php

namespace Opg\Lpa\Pdf\Service;

use Opg\Lpa\Pdf\Worker\Response\AbstractResponse;
use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Forms\Lp1f;
use Opg\Lpa\Pdf\Service\Forms\Lp1h;
use Opg\Lpa\Pdf\Service\Forms\Lp3;
use Opg\Lpa\Pdf\Service\Forms\Lpa120;
use Opg\Lpa\DataModel\Lpa\Document\Document;

use Opg\Lpa\DataModel\Lpa\StateChecker;
use Opg\Lpa\Pdf\Logger\Logger;

class Generator
{
    /**
     * Logger utility
     *
     * @var Logger
     */
    protected $logger;

    protected $formType;

    protected $lpa;

    protected $response;

    /**
     * Generator constructor
     *
     * @param $formType
     * @param Lpa $lpa
     * @param AbstractResponse $response
     */
    public function __construct($formType, Lpa $lpa, AbstractResponse $response)
    {
        $this->logger = Logger::getInstance();

        $this->formType = $formType;
        $this->lpa = $lpa;
        $this->response = $response;

        //  Copy pdf template files to ram if they haven't
        $this->copyPdfSourceToRam();
    }

    /**
     * Returns bool true if the document was successfully generated and passed to $response->save().
     * Otherwise returns a string describing the error is returned.
     *
     * @return bool|string
     */
    public function generate()
    {
        if ($this->lpa->validate()->hasErrors()) {
            //  The LPA is invalid
            $this->logger->info('LPA failed validation in PDF generator', [
                'lpaId' => $this->lpa->id
            ]);

            throw new RuntimeException('LPA failed validation');
        }

        $state = new StateChecker($this->lpa);

        # GENERATE THE PDF, STORING IN A LOCAL TMP FILE UNDER /tmp
        switch ($this->formType) {
            case 'LP1':
                if (!$state->canGenerateLP1()) {
                    throw new RuntimeException('LPA does not contain all the required data to generate a LP1');
                }

                switch ($this->lpa->document->type) {
                    case Document::LPA_TYPE_PF:
                        $pdf = new Lp1f($this->lpa);
                        break;
                    case Document::LPA_TYPE_HW:
                        $pdf = new Lp1h($this->lpa);
                        break;
                }

                break;
            case 'LP3':
                if (!$state->canGenerateLP3()) {
                    throw new RuntimeException('LPA does not contain all the required data to generate a LP3');
                }

                $pdf = new Lp3($this->lpa);

                break;
            case 'LPA120':
                if (!$state->canGenerateLPA120()) {
                    throw new RuntimeException('LPA does not contain all the required data to generate a LPA120');
                }

                $pdf = new Lpa120($this->lpa);

                break;
            default:
                throw new \UnexpectedValueException('Invalid form type: ' . $this->formType);
        }

        $pdf->generate();

        $filePath = $pdf->getPdfFilePath();

        $this->logger->info('PDF Filepath is ' . $filePath, [
            'lpaId' => $this->lpa->id
        ]);

        //  Pass the generated file to the response and save
        $this->response->save(new \SplFileInfo($filePath));

        //  Delete the local temp file
        $pdf->cleanup();

        return true;
    }

    /**
     * Copy LPA PDF template files into ram disk if they are not found on the ram disk.
     */
    private function copyPdfSourceToRam()
    {
        $assetsConfig = Config::getInstance()['service']['assets'];
        $templatePathOnDisk = $assetsConfig['template_path_on_ram_disk'];

        if (!\file_exists($templatePathOnDisk)) {
            $this->logger->info('Making template path on RAM disk', [
                'lpaId' => $this->lpa->id
            ]);

            \mkdir($templatePathOnDisk, 0777, true);
        }

        foreach (glob($assetsConfig['source_template_path'] . '/*.pdf') as $pdfSource) {
            $pathInfo = pathinfo($pdfSource);

            if (!\file_exists($templatePathOnDisk . '/' . $pathInfo['basename'])) {
                $dest = $templatePathOnDisk . '/' . $pathInfo['basename'];

                $this->logger->info('Copying PDF source to RAM disk', [
                    'lpaId'       => $this->lpa->id,
                    'destination' => $dest,
                ]);

                copy($pdfSource, $dest);
            }
        }
    }
}
