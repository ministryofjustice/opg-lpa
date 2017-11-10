<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\ContinuationSheet1 as ContinuationSheet1Pdf;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet1
 * @package Opg\Lpa\Pdf\Aggregator
 */
class ContinuationSheet1 extends AbstractAggregator
{
    /**
     * Constants
     */
    const MAX_ACTORS_CONTINUATION_SHEET_1 = 2;

    /**
     * @var array
     */
    private $actorGroups;

    /**
     * @param Lpa|null $lpa
     * @param array $primaryAttorneys
     * @param array $replacementAttorneys
     * @param array $peopleToNotify
     */
    public function __construct(Lpa $lpa = null, array $primaryAttorneys, array $replacementAttorneys, array $peopleToNotify)
    {
        //  Set up all the additional actors for processing
        $this->actorGroups = [
            'primaryAttorney'     => $primaryAttorneys,
            'replacementAttorney' => $replacementAttorneys,
            'peopleToNotify'      => $peopleToNotify,
        ];

        parent::__construct($lpa);
    }

    /**
     * Create the PDF in preparation for it to be generated - this function alone will not save a copy to the file system
     *
     * @param Lpa $lpa
     */
    protected function create(Lpa $lpa)
    {
        //  Loop through the actors and extract sets to send to the continuation sheet PDF for processing
        $actorsPackage = [];
        $actorCount = 0;

        foreach ($this->actorGroups as $actorType => $actors) {
            foreach ($actors as $actor) {
                //  If there is no space for this actor type at the moment create it now
                if (!array_key_exists($actorType, $actorsPackage)) {
                    $actorsPackage[$actorType] = [];
                }

                //  Add the actor and increment the count
                $actorsPackage[$actorType][] = $actor;
                $actorCount++;

                //  If ready send to PDF object
                if ($actorCount == self::MAX_ACTORS_CONTINUATION_SHEET_1) {
                    $this->addPdf(new ContinuationSheet1Pdf($lpa, $actorsPackage));

                    //  Clear the package and reset the count ready to start again
                    $actorsPackage = [];
                    $actorCount = 0;
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return count($this->pdfs) * 2;  //  2 pages per PDF
    }
}
