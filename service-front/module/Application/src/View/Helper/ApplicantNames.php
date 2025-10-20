<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Application\View\Helper\Traits\ConcatNamesTrait;
use MakeShared\DataModel\Lpa\Lpa;

class ApplicantNames extends AbstractHelper
{
    use ConcatNamesTrait;

    public function __invoke(Lpa $lpa)
    {

        if (!isset($lpa->document->whoIsRegistering)) {
            return;
        }

        if ($lpa->document->whoIsRegistering === 'donor') {
            return 'the donor';
        }

        if (is_array($lpa->document->whoIsRegistering) && is_array($lpa->document->primaryAttorneys)) {
            $humans = [];


            foreach ($lpa->document->primaryAttorneys as $attorney) {
                if (in_array($attorney->id, $lpa->document->whoIsRegistering)) {
                    $humans[] = $attorney;
                }
            }

            return $this->concatNames($humans);
        }
    }
}
