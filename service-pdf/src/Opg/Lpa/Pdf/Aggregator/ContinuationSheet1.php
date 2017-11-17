<?php

namespace Opg\Lpa\Pdf\Aggregator;

use Opg\Lpa\Pdf\ContinuationSheet1 as ContinuationSheet1Pdf;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * Class ContinuationSheet1
 * @package Opg\Lpa\Pdf\Aggregator
 */
class ContinuationSheet1 extends AbstractContinuationSheetAggregator
{
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
        $actorsPackages = [];
        $actorCount = 0;

        //  Divide the actors up into packages and use them to construct the individual continuation sheets
        foreach ($this->actorGroups as $actorType => $actors) {
            foreach ($actors as $actor) {
                //  If we have filled a package then increment the count
                $idx = (int) floor($actorCount / ContinuationSheet1Pdf::MAX_ACTORS_CONTINUATION_SHEET_1);

                //  If the required place in the packages array doesn't exist create it now
                if (!isset($actorsPackages[$idx])) {
                    $actorsPackages[$idx] = [];
                }

                if (!isset($actorsPackages[$idx][$actorType])) {
                    $actorsPackages[$idx][$actorType] = [];
                }

                //  Add the actor to the package
                $actorsPackages[$idx][$actorType][] = $actor;

                $actorCount++;
            }
        }

        //  Loop through the actor packages and create the continuation sheets
        foreach ($actorsPackages as $actorsPackage) {
            $this->addPdf(new ContinuationSheet1Pdf($lpa, $actorsPackage));
        }
    }
}
