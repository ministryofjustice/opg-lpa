<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Opg\Lpa\DataModel\Lpa\Formatter as LpaFormatter;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ContinuationSheets extends AbstractService
{
    /**
     * Gathers an array on conditions where continuation sheet(s) would be generated in the PDF.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getContinuationNoteKeys(Lpa $lpa)
    {
        //Array of keys to know which extra notes to show in template for continuation sheets
        $continuationNoteKeys = array();
        $extraBlockPeople = null;
        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);

        if ($paCount > 4 && $raCount > 2 && $pnCount > 4) {
            $extraBlockPeople = 'ALL_PEOPLE_OVERFLOW';
        } elseif ($paCount > 4 && $raCount > 2) {
            $extraBlockPeople =  'ALL_ATTORNEY_OVERFLOW';
        } elseif ($paCount > 4 && $pnCount > 4) {
            $extraBlockPeople =  'PRIMARY_ATTORNEY_AND_NOTIFY_OVERFLOW';
        } elseif ($raCount > 2 &&  $pnCount > 4) {
            $extraBlockPeople =  'REPLACEMENT_ATTORNEY_AND_NOTIFY_OVERFLOW';
        } elseif ($paCount > 4) {
            $extraBlockPeople =  'PRIMARY_ATTORNEY_OVERFLOW';
        } elseif ($raCount > 2) {
            $extraBlockPeople =  'REPLACEMENT_ATTORNEY_OVERFLOW';
        } elseif ($pnCount > 4) {
            $extraBlockPeople =  'NOTIFY_OVERFLOW';
        }

        if ($extraBlockPeople != null) {
            array_push($continuationNoteKeys, $extraBlockPeople);
        }

        if ($paCount > 4 || $raCount > 2 || $pnCount > 4) {
            array_push($continuationNoteKeys, 'ANY_PEOPLE_OVERFLOW');
        }

        if (
            isset($lpa->document->primaryAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->howDetails) ||
            isset($lpa->document->replacementAttorneyDecisions->when)
        ) {
            array_push($continuationNoteKeys, 'HAS_ATTORNEY_DECISIONS');
        }

        if (isset($lpa->document->donor)) {
            if (!$lpa->document->donor->canSign) {
                array_push($continuationNoteKeys, 'CANT_SIGN');
            }
        }

        $someAttorneyIsTrustCorp = false;

        foreach ($lpa->document->primaryAttorneys as $attorney) {
            if (isset($attorney->number)) {
                $someAttorneyIsTrustCorp = true;
            }
        }

        foreach ($lpa->document->replacementAttorneys as $attorney) {
            if (isset($attorney->number)) {
                $someAttorneyIsTrustCorp = true;
            }
        }

        if ($someAttorneyIsTrustCorp) {
            array_push($continuationNoteKeys, 'HAS_TRUST_CORP');
        }

        // The following line is taken from the PDF service.
        $allowedChars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) *
          LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;
        $lpaDocument = $lpa->getDocument();
        if (
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            array_push($continuationNoteKeys, 'LONG_INSTRUCTIONS_OR_PREFERENCES');
        }

        return $continuationNoteKeys;
    }
}
