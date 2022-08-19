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
                    'isDonor' => true,
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

        $continuationSheets = [];
        $cs1 = in_array('ANY_PEOPLE_OVERFLOW', $continuationNoteKeys);
        $cs2 = (
            in_array('LONG_INSTRUCTIONS_OR_PREFERENCES', $continuationNoteKeys) or
            in_array('HAS_ATTORNEY_DECISIONS', $continuationNoteKeys)
        );
        $cs3 = in_array('CANT_SIGN', $continuationNoteKeys);
        $cs4 = in_array('HAS_TRUST_CORP', $continuationNoteKeys);

        if ($cs1) {
            $continuationSheets[] = 1;
        }
        if ($cs2) {
            $continuationSheets[] = 2;
        }
        if ($cs3) {
            $continuationSheets[] = 3;
        }
        if ($cs4) {
            $continuationSheets[] = 4;
        }

        return [
            'applicants' => $applicants,
            'continuationNoteKeys' => $continuationNoteKeys,
            'continuationSheets' => $continuationSheets
        ];
    }
}
