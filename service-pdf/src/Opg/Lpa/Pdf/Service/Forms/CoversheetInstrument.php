<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class CoversheetInstrument extends AbstractCoversheet
{
    /**
     * Filename of the PDF template to use
     *
     * @var string|array
     */
    protected $pdfTemplateFile =  [
        Document::LPA_TYPE_PF => 'LP1F_CoversheetInstrument.pdf',
        Document::LPA_TYPE_HW => 'LP1H_CoversheetInstrument.pdf',
    ];
}
