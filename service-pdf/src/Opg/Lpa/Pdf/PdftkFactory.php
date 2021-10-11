<?php

namespace Opg\Lpa\Pdf;

use mikehaertl\pdftk\Pdf as PdftkPdf;

class PdftkFactory
{
    private $command;

    /**
     * @param string $command Custom pdftk command; if not set, defaults to 'pdftk'
     */
    public function __construct(string $command = 'pdftk')
    {
        $this->command = $command;
    }

    public function getPdftkCommand()
    {
        return $this->command;
    }

    public function create($pdf)
    {
        return new PdftkPdf($pdf, ['command' => $this->command]);
    }
}
