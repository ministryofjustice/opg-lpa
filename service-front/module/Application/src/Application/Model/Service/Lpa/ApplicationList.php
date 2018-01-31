<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Common\LongName;
use DateTime;

/**
 * Used for accessing a list of LPAs owned by the current user.
 *
 * Class ApplicationList
 * @package Application\Model\Service\Lpa
 */
class ApplicationList extends AbstractService
{
    /**
     * Get a summary of LPAs from the API utilising the search string if one was provided
     * If no page number if provided then get all summaries
     *
     * @param string $search
     * @param int $page
     * @param int $itemsPerPage
     * @return array
     */
    public function getLpaSummaries($search = null, $page = null, $itemsPerPage = null)
    {
        //  Construct the query params
        $queryParams = [
            'search' => $search,
        ];

        //  If valid page parameters are provided then add them to the API query
        if (is_numeric($page) && $page > 0 && is_numeric($itemsPerPage) && $itemsPerPage > 0) {
            $queryParams = array_merge($queryParams, [
                'page'    => $page,
                'perPage' => $itemsPerPage,
            ]);
        }

        $applicationsSummary = $this->getLpaApplicationService()->getApplicationList($queryParams);

        //  If there are LPAs returned, change them into standard class objects for use
        $lpas = [];

        if (isset($applicationsSummary['applications']) && is_array($applicationsSummary['applications'])) {
            foreach ($applicationsSummary['applications'] as $application) {
                //  Get the Donor name
                $donorName = '';

                if ($application->document->donor instanceof Donor && $application->document->donor->name instanceof LongName) {
                    $donorName = (string) $application->document->donor->name;
                }

                //  Get the progress string
                $progress = 'Started';

                if ($application->completedAt instanceof DateTime) {
                    $progress = 'Completed';
                } elseif ($application->createdAt instanceof DateTime) {
                    $progress = 'Created';
                }

                //  Create a record for the returned LPA
                $lpa = new \stdClass();

                $lpa->id = $application->id;
                $lpa->version = 2;
                $lpa->donor = $donorName;
                $lpa->type = $application->document->type;
                $lpa->updatedAt = $application->updatedAt;
                $lpa->progress = $progress;

                $lpas[] = $lpa;
            }

            //  Swap the stdClass LPAs in
            $applicationsSummary['applications'] = $lpas;
        }

        return $applicationsSummary;
    }
}
