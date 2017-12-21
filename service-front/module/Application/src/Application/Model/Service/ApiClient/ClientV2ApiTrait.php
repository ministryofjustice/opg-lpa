<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use Opg\Lpa\DataModel\Lpa\Lpa;

trait ClientV2ApiTrait
{
    /**
     * Returns all LPAs for the current user.
     *
     * @param array $query
     * @return array|Exception\ResponseException
     */
    public function getApplicationList(array $query = [])
    {
        $applicationList = [];

        //  Construct the path to the API and create a URL
        $path = sprintf('/v2/users/%s/applications', $this->getUserId());
        $url = new Uri($this->apiBaseUri . $path);

        $response = $this->httpGet($url, $query);

        if ($response->getStatusCode() != 200) {
            return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
        }

        $body = json_decode($response->getBody(), true);

        //  Only check the contents if results where returned
        if ($body['count'] > 0) {
            if (!isset($body['_links']) || !isset($body['_embedded']['applications'])) {
                return new Exception\ResponseException('missing-fields', $response->getStatusCode(), $response);
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

    /**
     * Create a new LPA application.
     *
     * @return Response\Lpa|Exception\ResponseException
     */
    public function createApplication()
    {
        $path = sprintf('/v2/users/%s/applications', $this->getUserId());

        $url = new Uri($this->apiBaseUri . $path);

        try {
            $response = $this->httpPost($url, ['']);

            if ($response->getStatusCode() == 201) {
                return Response\Lpa::buildFromResponse($response);
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Get an application by lpaId
     *
     * @param $lpaId
     * @return bool|static
     */
    public function getApplication($lpaId)
    {
        $path = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $url = new Uri($this->apiBaseUri . $path);

        $response = $this->httpGet($url);

        return ($response->getStatusCode() == 200 ? Response\Lpa::buildFromResponse($response) : false);
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

        $url = new Uri($this->apiBaseUri . $path);

        $response = $this->httpPatch($url, $data);

        return Response\Lpa::buildFromResponse($response);
    }

    /**
     * Deletes an LPA application.
     *
     * @param $lpaId
     * @return true|Exception\ResponseException
     */
    public function deleteApplication($lpaId)
    {
        $path = sprintf('/v2/users/%s/applications/%d', $this->getUserId(), $lpaId);

        $url = new Uri($this->apiBaseUri . $path);

        $response = $this->httpDelete($url);

        if ($response->getStatusCode() == 204) {
            return true;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Sets the LPA's metadata
     *
     * @param string $lpaId
     * @param array $metadata
     * @return boolean
     */
    public function setMetaData($lpaId, array $metadata)
    {
        $this->updateApplication($lpaId, [
            'metadata' => $metadata
        ]);

        return true;
    }
}
