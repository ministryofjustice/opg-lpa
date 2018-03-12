<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Application\Model\Service\ApiClient\Response\Lpa as LpaResponse;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Common\LongName;
use DateTime;
use InvalidArgumentException;

class Application extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * By default we just pass requests onto the API Client.
     *
     * TODO - Remove this function when all functions have been refactored
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->apiClient, $name ])) {
            return call_user_func_array([$this->apiClient, $name], $arguments);
        }

        throw new InvalidArgumentException("Unknown method $name called");
    }




    /**
     * Get an application by lpaId
     *
     * @param $lpaId
     * @return bool|static
     */
    public function getApplication($lpaId)
    {
        $response = $this->apiClient->httpGet(sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId));

        return ($response->getStatusCode() == 200 ? LpaResponse::buildFromResponse($response) : false);
    }

    /**
     * Create a new LPA application
     *
     * @return LpaResponse|ResponseException
     */
    public function createApplication()
    {
        $path = sprintf('/v2/users/%s/applications', $this->getUserId());

        try {
            $response = $this->apiClient->httpPost($path);

            if ($response->getStatusCode() == 201) {
                return LpaResponse::buildFromResponse($response);
            }
        } catch (ResponseException $e) {
            return $e;
        }

        return new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Update application with the provided data
     *
     * @param $lpaId
     * @param array $data
     * @return static
     */
    public function updateApplication($lpaId, array $data)
    {
        $path = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $response = $this->apiClient->httpPatch($path, $data);

        return LpaResponse::buildFromResponse($response);
    }

    /**
     * Deletes an LPA application
     *
     * @param $lpaId
     * @return true|ResponseException
     */
    public function deleteApplication($lpaId)
    {
        $path = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $response = $this->apiClient->httpDelete($path);

        if ($response->getStatusCode() == 204) {
            return true;
        }

        return new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

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

        $applicationsSummary = $this->getApplicationList($queryParams);

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

    /**
     * Returns all LPAs for the user
     *
     * TODO - Fold this logic into the getLpaSummaries function above as it's the only place it used
     *
     * @param array $query
     * @return ResponseException|array
     */
    private function getApplicationList(array $query = [])
    {
        $applicationList = [];

        $response = $this->apiClient->httpGet(sprintf('/v2/users/%s/applications', $this->getUserId()), $query);

        if ($response->getStatusCode() != 200) {
            return new ResponseException('unknown-error', $response->getStatusCode(), $response);
        }

        $body = json_decode($response->getBody(), true);

        //  Only check the contents if results where returned
        if ($body['count'] > 0) {
            if (!isset($body['_links']) || !isset($body['_embedded']['applications'])) {
                return new ResponseException('missing-fields', $response->getStatusCode(), $response);
            }

            //  If there are applications present then process them
            foreach ($body['_embedded']['applications'] as $application) {
                $applicationList[] = new Lpa($application);
            }
        }

        //  Return a summary of the application list
        return [
            'applications' => $applicationList,
            'total'        => $body['total'],
        ];
    }
}
