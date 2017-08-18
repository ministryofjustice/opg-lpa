<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class CoversheetInstrument extends AbstractCoversheet
{
    /**
     * @return array
     */
    protected $coversheetTemplateFiles = [
        Document::LPA_TYPE_PF => 'LP1F_CoversheetInstrument.pdf',
        Document::LPA_TYPE_HW => 'LP1H_CoversheetInstrument.pdf',
    ];
}
