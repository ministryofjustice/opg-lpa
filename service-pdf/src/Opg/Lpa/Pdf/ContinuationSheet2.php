<?php

namespace Opg\Lpa\Pdf;

use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet2
 * @package Opg\Lpa\Pdf
 */
class ContinuationSheet2 extends AbstractContinuationSheet
{
    /**
     * PDF template file name (without path) for this PDF object
     *
     * @var
     */
    protected $templateFileName = 'LPC_Continuation_Sheet_2.pdf';

    /**
     * @var
     */
    private $cs2Type;

    /**
     * @var
     */
    private $content;

    /**
     * @var
     */
    private $isContinued;

    /**
     * @param Lpa $lpa
     * @param $cs2Type
     * @param $content
     * @param $isContinued
     */
    public function __construct(Lpa $lpa, $cs2Type, $content, $isContinued)
    {
        $this->cs2Type = $cs2Type;
        $this->content = $content;
        $this->isContinued = $isContinued;

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

        $this->setData('cs2-is', $this->cs2Type)
             ->setData('cs2-content', $this->content);

        if ($this->isContinued) {
            $this->setData('cs2-continued', '(Continued)');
        }

        //  Set footer data
        $this->setFooter('cs2-footer-right', 'cs2');
    }
}
