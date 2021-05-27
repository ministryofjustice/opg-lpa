<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\ContinuationSheet2 as ContinuationSheet2Pdf;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\PdftkFactory;
use Opg\Lpa\Pdf\Traits\LongContentTrait;
use Exception;

/**
 * Class ContinuationSheet2
 * @package Opg\Lpa\Pdf\Aggregator
 */
class ContinuationSheet2 extends AbstractContinuationSheetAggregator
{
    use LongContentTrait;

    /**
     * @var
     */
    private $cs2Type;

    /**
     * @param Lpa|null $lpa
     * @param $csType
     */
    public function __construct(Lpa $lpa = null, $csType, ?PdftkFactory $pdftkFactory = null)
    {
        //  Set up all the additional actors for processing
        $this->cs2Type = $csType;

        parent::__construct($lpa, null, [], $pdftkFactory);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     * @throws Exception
     */
    protected function create(Lpa $lpa)
    {
        //  Get the full content and determine the starting page
        $page = 1;
        $fullContent = null;

        switch ($this->cs2Type) {
            case ContinuationSheet2Pdf::CS2_TYPE_PRIMARY_ATTORNEYS_DECISIONS:
                $fullContent = $lpa->document->primaryAttorneyDecisions->howDetails;
                break;
            case ContinuationSheet2Pdf::CS2_TYPE_REPLACEMENT_ATTORNEYS_STEP_IN:
                $fullContent = $this->getHowWhenReplacementAttorneysCanActContent($lpa->document);
                break;
            case ContinuationSheet2Pdf::CS2_TYPE_PREFERENCES:
                $fullContent = $lpa->document->preference;
                $page = 2;
                break;
            case ContinuationSheet2Pdf::CS2_TYPE_INSTRUCTIONS:
                $fullContent = $lpa->document->instruction;
                $page = 2;
                break;
            default:
                throw new Exception('Unexpected content type found for continuation sheet 2: ' . $this->cs2Type);
        }

        //  Loop through the details and pass chunks of content to the PDF object to render
        do {
            //  Get the correct block of content - if we request a page too far then an exception will be thrown
            $contentFullyProcessed = false;

            try {
                //  TODO - implement a check for this instead of just getting the content...
                if (in_array($this->cs2Type, [
                    ContinuationSheet2Pdf::CS2_TYPE_PREFERENCES,
                    ContinuationSheet2Pdf::CS2_TYPE_INSTRUCTIONS,
                ])) {
                    $content = $this->getInstructionsAndPreferencesContent($fullContent, $page);
                } else {
                    $content = $this->getContinuationSheet2Content($fullContent, $page);
                }

                $this->addPdf(new ContinuationSheet2Pdf($lpa, $this->cs2Type, $fullContent, $page, $this->pdftkFactory));
            } catch (Exception $ignore) {
                //  We've requested a page too far so break the loop
                $contentFullyProcessed = true;
            }

            $page++;
        } while (!$contentFullyProcessed);
    }
}
