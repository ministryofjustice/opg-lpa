<?php

namespace Opg\Lpa\Pdf;

use mikehaertl\pdftk\Pdf as PdftkPdf;

class PdftkFactory
{
    /**
     * @var string
     */
    private string $command;

    /**
     * @param string $command Custom pdftk command; if not set, defaults to 'pdftk'
     */
    public function __construct(string $command = 'pdftk')
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getPdftkCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $pdf
     *
     * @return PdftkPdf
     */
    public function create(string $pdf): PdftkPdf
    {
        return new PdftkPdf($pdf, ['command' => $this->command]);
    }
}
