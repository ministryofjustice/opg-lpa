<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class CoversheetRegistration extends AbstractCoversheet
{
    /**
     * @return array
     */
    protected $coversheetTemplateFiles = [
        Document::LPA_TYPE_PF => 'LP1F_CoversheetRegistration.pdf',
        Document::LPA_TYPE_HW => 'LP1H_CoversheetRegistration.pdf',
    ];
}
