<?php

namespace Application\View;

use Application\Model\Service\Lpa\ContinuationSheets;
use Laminas\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Lpa;

class DateCheckViewModelHelper
{
    public static function build(Lpa $lpa): array
    {
        $applicants = [];

        if ($lpa->completedAt !== null) {
            if ($lpa->document->whoIsRegistering === 'donor') {
                $applicants[0] = [
                    'name' => $lpa->document->donor->name,
                    'isHuman' => true,
                ];
            } elseif (is_array($lpa->document->whoIsRegistering)) {
                // Applicant is one or more primary attorneys
                foreach ($lpa->document->whoIsRegistering as $id) {
                    foreach ($lpa->document->primaryAttorneys as $primaryAttorney) {
                        if ($id == $primaryAttorney->id) {
                            $applicants[] = [
                                'name' => $primaryAttorney->name,
                                'isHuman' => isset($primaryAttorney->dob),
                            ];
                            break;
                        }
                    }
                }
            }
        }

        $continuationSheets = new ContinuationSheets();
        $continuationNoteKeys = $continuationSheets->getContinuationNoteKeys($lpa);

        return [
            'applicants' => $applicants,
            'continuationNoteKeys' => $continuationNoteKeys
        ];
    }
}
