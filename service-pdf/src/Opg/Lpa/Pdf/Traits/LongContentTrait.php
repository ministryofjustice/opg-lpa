<?php

namespace Opg\Lpa\Pdf\Traits;

use Opg\Lpa\DataModel\Lpa\Formatter as LpaFormatter;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Document;

/**
 * Trait LongContentTrait
 * @package Opg\Lpa\Pdf\Traits
 */
trait LongContentTrait
{
    /**
     * @var int
     */
    private $fullWidthNumberOfChars = LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_WIDTH;

    /**
     * @var int
     */
    private $instructionsPreferencesBoxRows = LpaFormatter::INSTRUCTIONS_PREFERENCES_ROW_COUNT;

    /**
     * @var int
     */
    private $continuationSheet2BoxRows = 14;

    /**
     * Return a boolean to indicate if the content fills the preferences/instructions text box entirely
     *
     * @return boolean
     */
    private function fillsInstructionsPreferencesBox($content)
    {
        $flatContent = $this->flattenTextContent($content);

        return strlen($flatContent) > $this->getInstructionsPreferencesBoxSize();
    }

    /**
     * @return int
     */
    private function getInstructionsPreferencesBoxSize()
    {
        return ($this->fullWidthNumberOfChars + 2) * $this->instructionsPreferencesBoxRows;
    }

    /**
     * @return int
     */
    private function getContinuationSheet2BoxSize()
    {
        return ($this->fullWidthNumberOfChars + 2) * $this->continuationSheet2BoxRows;
    }

    /**
     * Get chunks of the instructions/preferences content by page number
     * If no page number is provided then the first page will be returned
     *
     * @param string $content
     * @param int $pageNo
     * @return string|null
     */
    private function getInstructionsAndPreferencesContent($content, $pageNo = 1)
    {
        $flatContent = $this->flattenTextContent($content);

        if ($pageNo == 1) {
            return "\r\n" . substr($flatContent, 0, $this->getInstructionsPreferencesBoxSize());
        } else {
            //  Remove the first part of the content (that will populate the main Lp1 form) and then pass the
            //  content down to the CS2 function to get more data
            $flatContent = substr($flatContent, $this->getInstructionsPreferencesBoxSize());

            //  Use an adjust page number to make sure we get the correct data
            return $this->getContinuationSheet2Content($flatContent, ($pageNo - 1));
        }
    }

    /**
     * @param string $content
     * @param int $pageNo
     * @return null|string
     */
    private function getContinuationSheet2Content($content, $pageNo)
    {
        $flatContent = $this->flattenTextContent($content);

        $chunks = str_split($flatContent, $this->getContinuationSheet2BoxSize());

        if (isset($chunks[$pageNo - 1])) {
            return "\r\n" . $chunks[$pageNo - 1];
        } else {
            return null;
        }
    }

    /**
     * Convert all new lines with spaces to fill out to the end of each line
     *
     * @param string $contentIn
     * @return string
     */
    private function flattenTextContent($contentIn)
    {
        return LpaFormatter::flattenInstructionsOrPreferences($contentIn);
    }

    /**
     * Get the content that describes how and when replacement attorneys can act
     * This is done in this trait because the results (and whether there is a result) is useful in multiple places
     * The logic is messy but by housing it here we can contain it
     *
     * @param Document $lpaDocument
     * @return string
     */
    private function getHowWhenReplacementAttorneysCanActContent(Document $lpaDocument)
    {
        $content = '';

        $primaryAttorneys = $lpaDocument->getPrimaryAttorneys();
        $primaryHow = null;
        if (count($primaryAttorneys) > 1) {
            $primaryHow = $lpaDocument->getPrimaryAttorneyDecisions()->getHow();
        }

        $replacementAttorneys = $lpaDocument->getReplacementAttorneys();
        $replacementHow = null;
        $replacementWhen = null;
        $replacementHowDetails = null;
        $replacementWhenDetails = null;
        if (count($replacementAttorneys) > 1) {
            $replacementDecisions = $lpaDocument->getReplacementAttorneyDecisions();
            $replacementHow = $replacementDecisions->getHow();
            $replacementWhen = $replacementDecisions->getWhen();
            $replacementHowDetails = $replacementDecisions->getHowDetails();
            $replacementWhenDetails = $replacementDecisions->getWhenDetails();
        }

        if (
            count($replacementAttorneys) > 1 &&
            (count($primaryAttorneys) == 1 || $primaryHow == PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)
        ) {
            switch ($replacementHow) {
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                    $content = "Replacement attorneys are to act jointly and severally\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                    $content = "Replacement attorneys are to act jointly for some decisions and jointly and " .
                        "severally for others, as below:\r\n" .
                        $replacementHowDetails . "\r\n";
                    break;
                case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                    // default arrangement
                    break;
            }
        } elseif ($primaryHow = PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY) {
            if (count($replacementAttorneys) == 1) {
                switch ($replacementWhen) {
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST:
                        // default arrangement, as per how primary attorneys making decision arrangement
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST:
                        $content = "Replacement attorney to step in only when none of the original " .
                            "attorneys can act\r\n";
                        break;
                    case ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS:
                        $content = "How replacement attorneys will replace the original attorneys:\r\n" .
                            $replacementWhenDetails;
                        break;
                }
            } elseif (count($replacementAttorneys) > 1) {
                if ($replacementWhen == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST) {
                    $content = "Replacement attorneys to step in only when none of the original attorneys can act\r\n";

                    switch ($replacementHow) {
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY:
                            $content .= "Replacement attorneys are to act jointly and severally\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_DEPENDS:
                            $content .= "Replacement attorneys are to act joint for some decisions, joint and " .
                                "several for other decisions, as below:\r\n" .
                                $replacementHowDetails . "\r\n";
                            break;
                        case ReplacementAttorneyDecisions::LPA_DECISION_HOW_JOINTLY:
                            // default arrangement
                            $content = "";
                            break;
                    }
                } elseif ($replacementWhen == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $content = "How replacement attorneys will replace the original attorneys:\r\n" .
                        $replacementWhenDetails;
                }
            }
        }

        return $content;
    }
}
