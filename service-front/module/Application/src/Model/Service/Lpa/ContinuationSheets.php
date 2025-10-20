<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use MakeShared\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Formatter as LpaFormatter;
use MakeShared\DataModel\Lpa\Lpa;

class ContinuationSheets
{
    /*
    This covers the scenarios for these criteria where cs2=yes (see below).
    Although some are redundant, they are retained here to make it easier to
    see how they marry up with the test cases we need to cover.

    pa = primary attorney(s)
    ra = replacement attorney(s)

    how multiple attorneys act:
    j = jointly
    js = jointly and severally
    jsjso = jointly some, jointly and severally others

    when replacement attorneys step in:
    one = as soon as one of the original attorneys cannot act
    none = when none of the original attorneys can act
    other = some other arrangement

    c2=yes|no - whether the combination yields one or more continuation sheet 2s

    Possible combinations of primary and replacement attorneys and how/when
    decisions are:

    1.  Single pa, single ra; cs2=no
    2.  Single pa, multiple ras how=j; cs2=no
    3.  Single pa, multiple ras how=js; cs2=yes
    4.  Single pa, multiple ras how=jsjso; cs2=yes
    5.  Multiple pas how=j, single ra; cs2=no
    6.  Multiple pas how=js, single ra when=one; cs2=no
    7.  Multiple pas how=js, single ra when=none; cs2=yes
    8.  Multiple pas how=js, single ra when=other; cs2=yes
    9.  Multiple pas how=jsjso, single ra; cs2=yes
    10. Multiple pas how=j, multiple ras how=j; cs2=no
    11. Multiple pas how=j, multiple ras how=js; cs2=yes
    12. Multiple pas how=j, multiple ras how=jsjso; cs2=yes
    13. Multiple pas how=js, multiple ras when=one; cs2=no
    14. Multiple pas how=js, multiple ras when=none how=j; cs2=no
    15. Multiple pas how=js, multiple ras when=none how=js; cs2=yes
    16. Multiple pas how=js, multiple ras when=none how=jsjso; cs2=yes
    17. Multiple pas how=js, multiple ras when=other; cs2=yes
    18. Multiple pas how=jsjso, multiple ras; cs2=yes
    19. Single pa, no ra; cs2=no
    20. Multiple pas how=j, no ra; cs2=no
    21. Multiple pas how=js, no ra; cs2=no
    22. Multiple pas how=jsjso, no ra; cs2=yes
    */
    private function hasAttorneyDecisions(Lpa $lpa)
    {
        // attorney-related criteria we use to figure out whether a cs2 is present
        $paHow = null;
        if (isset($lpa->document->primaryAttorneyDecisions->how)) {
            $paHow = $lpa->document->primaryAttorneyDecisions->how;
        }

        $raHow = null;
        if (isset($lpa->document->replacementAttorneyDecisions->how)) {
            $raHow = $lpa->document->replacementAttorneyDecisions->how;
        }

        $raWhen = null;
        if (isset($lpa->document->replacementAttorneyDecisions->when)) {
            $raWhen = $lpa->document->replacementAttorneyDecisions->when;
        }

        // early return if we have no data about decisions; every case
        // which returns true must have at least one of these set,
        // so if the lpa doesn't have any of these set we can't figure
        // anything out
        if (is_null($paHow) && is_null($raHow) && is_null($raWhen)) {
            return false;
        }

        $numPaAttorneys = -1;
        if (isset($lpa->document->primaryAttorneys)) {
            $numPaAttorneys = count($lpa->document->primaryAttorneys);
        }

        $numRepAttorneys = -1;
        if (isset($lpa->document->replacementAttorneys)) {
            $numRepAttorneys = count($lpa->document->replacementAttorneys);
        }

        // criteria we use to decide whether we have attorney decisions
        $singlePa = $numPaAttorneys == 1;
        $multiplePas = $numPaAttorneys > 1;

        $zeroRas = $numRepAttorneys == 0;
        $singleRa = $numRepAttorneys == 1;
        $multipleRas = $numRepAttorneys > 1;

        $joint = AbstractDecisions::LPA_DECISION_HOW_JOINTLY;
        $jointSev = AbstractDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY;
        $jointSomeJointSevOther = AbstractDecisions::LPA_DECISION_HOW_DEPENDS;

        $whenNone = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST;
        $whenOther = ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS;

        // cover all the cs2=yes conditions (see comment on method);
        // using a switch/case here because it makes the coverage report more useful,
        // showing which case are/aren't covered
        switch (true) {
            case $singlePa && $multipleRas && $raHow == $jointSev:
                return true;
            case $singlePa && $multipleRas && $raHow == $jointSomeJointSevOther:
                return true;
            case $multiplePas && $paHow == $joint && $multipleRas && $raHow == $jointSev:
                return true;
            case $multiplePas && $paHow == $joint && $multipleRas && $raHow == $jointSomeJointSevOther:
                return true;
            case $multiplePas && $paHow == $jointSev && $singleRa && $raWhen == $whenNone:
                return true;
            case $multiplePas && $paHow == $jointSev && $singleRa && $raWhen == $whenOther:
                return true;
            case $multiplePas && $paHow == $jointSev && $multipleRas && $raWhen == $whenNone && $raHow == $jointSev:
                return true;
            case (
                $multiplePas && $paHow == $jointSev && $multipleRas &&
                $raWhen == $whenNone && $raHow == $jointSomeJointSevOther
            ):
                return true;
            case $multiplePas && $paHow == $jointSev && $multipleRas && $raWhen == $whenOther:
                return true;
            case $multiplePas && $paHow == $jointSomeJointSevOther && $zeroRas:
                return true;
            case $multiplePas && $paHow == $jointSomeJointSevOther && $singleRa:
                return true;
            case $multiplePas && $paHow == $jointSomeJointSevOther && $multipleRas:
                return true;
        }

        return false;
    }

    /**
     * Gathers an array on conditions where continuation sheet(s) would be generated in the PDF.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getContinuationNoteKeys(Lpa $lpa): array
    {
        //Array of keys to know which extra notes to show in template for continuation sheets
        $continuationNoteKeys = [];
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

        if ($this->hasAttorneyDecisions($lpa)) {
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
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getPreference())) > $allowedChars ||
            strlen(LpaFormatter::flattenInstructionsOrPreferences($lpaDocument->getInstruction())) > $allowedChars
        ) {
            array_push($continuationNoteKeys, 'LONG_INSTRUCTIONS_OR_PREFERENCES');
        }

        return $continuationNoteKeys;
    }
}
