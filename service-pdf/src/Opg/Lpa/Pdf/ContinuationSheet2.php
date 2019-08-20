<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Traits\LongContentTrait;
use Exception;

/**
 * Class ContinuationSheet2
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet2 extends AbstractContinuationSheet
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
     * PDF template file name (without path) for this PDF object
     *
     * @var string
     */
    protected $templateFileName = 'LPC_Continuation_Sheet_2.pdf';

    /**
     * @var string
     */
    private $cs2Type;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $isContinued = false;

    /**
     * @param Lpa $lpa
     * @param $cs2Type
     * @param $fullContent
     * @param $contentPage
     * @throws Exception
     */
    public function __construct(Lpa $lpa, $cs2Type, $fullContent, $contentPage)
    {
        //  Ensure that the content type and page number selected are allowed
        if (!is_int($contentPage) || $contentPage < 1) {
            throw new Exception('The requested content page must be a positive integer');
        } elseif (in_array($cs2Type, [self::CS2_TYPE_PREFERENCES, self::CS2_TYPE_INSTRUCTIONS]) && $contentPage == 1) {
            throw new Exception('Page 1 of the preferences and instructions can not be displayed on continuation sheet 2');
        }

        //  Get the correct piece of content to use
        if (in_array($cs2Type, [
            self::CS2_TYPE_PREFERENCES,
            self::CS2_TYPE_INSTRUCTIONS,
        ])) {
            $content = $this->getInstructionsAndPreferencesContent($fullContent, $contentPage);
        } else {
            $content = $this->getContinuationSheet2Content($fullContent, $contentPage);
        }

        //  If the content is null at this point then the continuation sheet can not be created
        if (is_null($content)) {
            throw new Exception(sprintf('Page %s can not be generated for content type %s', $contentPage, $cs2Type));
        }

        $this->cs2Type = $cs2Type;
        $this->content = $content;
        $this->isContinued = ($contentPage > 1);

        parent::__construct($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        parent::create($lpa);

        $this->setData('cs2-donor-full-name', (string) $lpa->document->donor->name)
             ->setData('cs2-is', $this->cs2Type)
             ->setData('cs2-content', $this->content)
             ->setData('cs2-continued', ($this->isContinued ? '(Continued)' : ''));

        //  Set footer data
        $this->setFooter('cs2-footer-right', 'cs2');
    }
}
