<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Opg\Lpa\DataModel\Lpa\Formatter as LpaFormatter;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ContinuationSheets
{
    /**
     * @var int
     */
    private $continuationSheet2BoxRows = 14;

    /**
     * Gathers an array on conditions where continuation sheet(s) would be generated in the LPA PDF.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getContinuationNoteKeys(Lpa $lpa): array
    {
        //Array of keys to know which extra notes to show in template for continuation sheets
        $continuationNoteKeys = array();
        $extraBlockPeople = null;
        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);

        switch (true) {
            case $paCount > 4 && $raCount > 2 && $pnCount > 4:
                $extraBlockPeople = 'ALL_PEOPLE_OVERFLOW';
                break;
            case $paCount > 4 && $raCount > 2:
                $extraBlockPeople = 'ALL_ATTORNEY_OVERFLOW';
                break;
            case $paCount > 4 && $pnCount > 4:
                $extraBlockPeople = 'PRIMARY_ATTORNEY_AND_NOTIFY_OVERFLOW';
                break;
            case $raCount > 2 && $pnCount > 4:
                $extraBlockPeople = 'REPLACEMENT_ATTORNEY_AND_NOTIFY_OVERFLOW';
                break;
            case $paCount > 4:
                $extraBlockPeople = 'PRIMARY_ATTORNEY_OVERFLOW';
                break;
            case $raCount > 2:
                $extraBlockPeople = 'REPLACEMENT_ATTORNEY_OVERFLOW';
                break;
            case $pnCount > 4:
                $extraBlockPeople = 'NOTIFY_OVERFLOW';
                break;
        }

        if ($extraBlockPeople != null) {
            $continuationNoteKeys[] = $extraBlockPeople;
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

        if (isset($lpa->document->donor) && !$lpa->document->donor->canSign) {
            array_push($continuationNoteKeys, 'CANT_SIGN');
        }

        $allAttorneys = array_merge($lpa->document->primaryAttorneys, $lpa->document->replacementAttorneys);
        foreach ($allAttorneys as $attorney) {
            if (isset($attorney->number)) {
                $continuationNoteKeys[] = 'HAS_TRUST_CORP';
                break;
            }
        }

        // The following line is taken from the PDF service.
        $allowedChars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) *
            LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;
        $lpaDocument = $lpa->getDocument();
        if (
            // do getPref or getInstr cover all CS2 scenarios?
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            array_push($continuationNoteKeys, 'LONG_INSTRUCTIONS_OR_PREFERENCES');
        }

        return $continuationNoteKeys;
    }

    /**
     * Works out if references to continuation sheets 1 and 2 need to be pluralised, ie. if
     * >1 of either will be generated in the LPA PDF, and returns an 's' to the view if so.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getCsPluraliser($lpa): array
    {
        $result = array();

        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);

        $overflowCount = 0;
        switch (true) {
            case $paCount > 4:
                $overflowCount += ($paCount - 4);
                // no break because we need the cumulative overflow count
            case $raCount > 2:
                $overflowCount += ($raCount - 2);
                // no break because we need the cumulative overflow count
            case $pnCount > 4:
                $overflowCount += ($pnCount - 4);
        }

        $cs1Pluraliser = '';
        if ($overflowCount > 2) {
            $cs1Pluraliser = 's';
        }

        $result['cs1'] = $cs1Pluraliser;

        $cs2Pluraliser = '';

        // The following lines are taken from the PDF service.
        $lpaChars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) *
          LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;
        $cs2Chars = (LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH + 2) * $this->continuationSheet2BoxRows;
        // Total chars allowed for LPA plus page 1 of CS2
        $allowedChars = $lpaChars + $cs2Chars;

        $lpaDocument = $lpa->getDocument();
        $thing = strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction()));
        var_dump($thing . ' out of ' . $allowedChars);
        var_dump('####' . $lpaDocument->getInstruction());
        if (
            // do getPref or getInstr cover all CS2 scenarios?
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            $cs2Pluraliser = 's';
        }

        $result['cs2'] = $cs2Pluraliser;

        return $result;
    }
}
