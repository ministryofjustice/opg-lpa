<?php

namespace Opg\Lpa\Pdf;

use mikehaertl\pdftk\Pdf as PdftkPdf;
use RuntimeException;

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
            $result = exec(sprintf('command -v %s', escapeshellarg($this->command)));

            if ($result === '') {
                $this->commandAvailable = false;
            }
        }

        return $this->commandAvailable;
    }

    /**
     * @returns string pdftk command
     */
    public function getPdftkCommand(): string
    {
        return $this->command;
    }

    /**
     * @param array|string $pdf Name(s) of PDF(s) to create
     * @throws RuntimeException if the pdftk command is not accessible
     */
    public function create(string|array $pdf): PdftkPdf
    {
        if (!$this->checkPdftkAvailable()) {
            throw new RuntimeException(
                "pdftk must be installed to generate PDFs; $this->command is not a valid pdftk executable"
            );
        }

        return new PdftkPdf($pdf, ['command' => $this->command]);
    }
}
