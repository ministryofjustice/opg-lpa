<?php

namespace Opg\Lpa\Pdf;

use mikehaertl\pdftk\Pdf as PdftkPdf;
use escapeshellarg;
use RuntimeException;
use pclose;
use popen;
use sprintf;

class PdftkFactory
{
    private $command;

    /** @var bool|null */
    private $commandAvailable = null;

    /**
     * @param string $command Custom pdftk command; if not set, defaults to 'pdftk'
     */
    public function __construct(string $command = 'pdftk')
    {
        $this->command = $command;
    }

    private function checkPdftkAvailable(): bool
    {
        if (is_null($this->commandAvailable)) {
            $this->commandAvailable = true;

            // "command" is POSIX-compatible and available on Mac and Linux systems,
            // but may not work if you decide to run this on Windows
            $process = popen(sprintf('command -v %s', escapeshellarg($this->command)), 'r');

            // pclose() returns the exit code from the opened process or -1 on error
            $checkCommand = pclose($process);

            if ($checkCommand !== 0) {
                $this->$commandAvailable = false;
            }
        }

        return $this->commandAvailable;
    }

    /**
     * @returns string pdftk command
     * @throws RuntimeException if the pdftk command is not accessible
     */
    public function getPdftkCommand(): string
    {
        if (!$this->checkPdftkAvailable()) {
            throw new RuntimeException(
                "pdftk must be installed to generate PDFs; $this->command is not a valid pdftk executable"
            );
        }

        return $this->command;
    }

    /**
     * @param array|string $pdf Name(s) of PDF(s) to create
     */
    public function create(string|array $pdf): PdftkPdf
    {
        return new PdftkPdf($pdf, ['command' => $this->command]);
    }
}
