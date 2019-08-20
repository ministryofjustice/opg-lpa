<?php

namespace Opg\Lpa\Pdf\Aggregator;

/**
 * Class AbstractContinuationSheetAggregator
 * @package Opg\Lpa\Pdf\Aggregator
 */
abstract class AbstractContinuationSheetAggregator extends AbstractAggregator
{
    /**
     * @return int
     */
    public function getPageCount()
    {
        return count($this->pdfs) * 2;  //  2 pages per PDF
    }
}
