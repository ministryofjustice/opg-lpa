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

    /**
     * @param string $command Custom pdftk command; if not set, defaults to 'pdftk'
     * @throws RuntimeException if the pdftk command is not accessible
     */
    public function __construct(string $command = 'pdftk')
    {
        // "command" is POSIX-compatible and available on Mac and Linux systems,
        // but may not work if you decide to run this on Windows
        $process = popen(sprintf('command -v %s', escapeshellarg($command)), 'r');

        // pclose() returns the exit code from the opened process or -1 on error
        $checkCommand = pclose($process);

        if ($checkCommand !== 0) {
            throw new RuntimeException('pdftk must be installed to generate PDFs');
        }

        $this->command = $command;
    }

    public function getPdftkCommand(): string
    {
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
