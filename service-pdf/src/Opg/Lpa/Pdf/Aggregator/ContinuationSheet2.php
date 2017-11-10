<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\ContinuationSheet2 as ContinuationSheet2Pdf;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Traits\LongContentTrait;

/**
 * Class ContinuationSheet2
 * @package Opg\Lpa\Pdf\Aggregator
 */
class ContinuationSheet2 extends AbstractContinuationSheetAggregator
{
    use LongContentTrait;

    /**
     * Constant
     */
    const CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS   = 'decisions';
    const CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN = 'how-replacement-attorneys-step-in';
    const CS2_TYPE_PREFERENCES                   = 'preferences';
    const CS2_TYPE_INSTRUCTIONS                  = 'instructions';

    /**
     * @var
     */
    private $cs2Type;

    /**
     * @param Lpa|null $lpa
     * @param $csType
     */
    public function __construct(Lpa $lpa = null, $csType)
    {
        //  Set up all the additional actors for processing
        $this->cs2Type = $csType;

        parent::__construct($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {

//TODO - Refactor this to cut down

        if ($this->cs2Type == self::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS) {
            //  Loop through the details and pass chunks of content to the PDF object to render
            $page = 1;

            do {
                $content = $this->getContinuationSheet2Content($lpa->document->primaryAttorneyDecisions->howDetails, $page);

                if (!is_null($content)) {
                    $isContinued = ($page > 1);
                    $this->addPdf(new ContinuationSheet2Pdf($lpa, $this->cs2Type, $content, $isContinued));
                    $page++;
                }
            } while (!is_null($content));
        } elseif ($this->cs2Type == self::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN) {
            //  Loop through the details and pass chunks of content to the PDF object to render
            $page = 1;

            $replacementAttorneysContent = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);

            do {
                $content = $this->getContinuationSheet2Content($replacementAttorneysContent, $page);

                if (!is_null($content)) {
                    $isContinued = ($page > 1);
                    $this->addPdf(new ContinuationSheet2Pdf($lpa, $this->cs2Type, $content, $isContinued));
                    $page++;
                }
            } while (!is_null($content));
        } elseif ($this->cs2Type == self::CS2_TYPE_INSTRUCTIONS) {
            //  Loop through the details and pass chunks of content to the PDF object to render
            $page = 2;

            do {
                $content = $this->getInstructionsAndPreferencesContent($lpa->document->instruction, $page);

                if (!is_null($content)) {
                    $this->addPdf(new ContinuationSheet2Pdf($lpa, $this->cs2Type, $content, true));
                    $page++;
                }
            } while (!is_null($content));
        } elseif ($this->cs2Type == self::CS2_TYPE_PREFERENCES) {
            //  Loop through the details and pass chunks of content to the PDF object to render
            $page = 2;

            do {
                $content = $this->getInstructionsAndPreferencesContent($lpa->document->preference, $page);

                if (!is_null($content)) {
                    $this->addPdf(new ContinuationSheet2Pdf($lpa, $this->cs2Type, $content, true));
                    $page++;
                }
            } while (!is_null($content));
        }
    }
}
