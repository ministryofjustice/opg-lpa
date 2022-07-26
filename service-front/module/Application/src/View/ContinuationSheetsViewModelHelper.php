<?php

namespace Application\View;

use Application\Model\Service\Lpa\ContinuationSheets;
use Laminas\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ContinuationSheetsViewModelHelper
{
    public static function build(Lpa $lpa): array
    {
        $continuationSheets = new ContinuationSheets();
        $continuationNoteKeys = $continuationSheets->getContinuationNoteKeys($lpa);
        return $continuationNoteKeys;
    }
}
