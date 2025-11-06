<?php

namespace Application\View;

use Application\Model\Service\Lpa\ContinuationSheets;
use MakeShared\DataModel\Lpa\Lpa;

class DateCheckViewModelHelper
{
    private static function lpaHasAttorneyDecisionDetail(Lpa $lpa)
    {
    }

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
                                'isDonor' => false,
                                'isHuman' => isset($primaryAttorney->dob),
                            ];
                            break;
                        }
                    }
                }
            }
        }

        $continuationNoteKeys = (new ContinuationSheets())->getContinuationNoteKeys($lpa);

        $continuationSheets = [];

        if (in_array('ANY_PEOPLE_OVERFLOW', $continuationNoteKeys)) {
            $continuationSheets[] = 1;
        }

        if (
            in_array('LONG_INSTRUCTIONS_OR_PREFERENCES', $continuationNoteKeys) ||
            in_array('HAS_ATTORNEY_DECISIONS', $continuationNoteKeys)
        ) {
            $continuationSheets[] = 2;
        }

        if (in_array('CANT_SIGN', $continuationNoteKeys)) {
            $continuationSheets[] = 3;
        }

        if (in_array('HAS_TRUST_CORP', $continuationNoteKeys)) {
            $continuationSheets[] = 4;
        }

        return [
            'applicants' => $applicants,
            'continuationSheets' => $continuationSheets
        ];
    }
}
