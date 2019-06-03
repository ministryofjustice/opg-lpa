<?php

namespace Application\Model\Service\ProcessingStatus;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use GuzzleHttp\Pool;
use GuzzleHttp\Client as HttpClient;

class Service extends AbstractService
{
    private const SIRIUS_STATUS_TO_LPA = [
            'Pending' => Lpa::SIRIUS_PROCESSING_STATUS_RECEIVED,
            'Perfect' => Lpa::SIRIUS_PROCESSING_STATUS_CHECKING,
            'Imperfect' => Lpa::SIRIUS_PROCESSING_STATUS_CHECKING,
            'Invalid' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
            'Rejected' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
            'Withdrawn' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
            'Registered' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
            'Cancelled' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
            'Revoked' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED
    ];

    /**
     * @var $httpClient HttpClient
     */
    private $httpClient;

    /**
     * @var $processingStatusServiceUri string
     */
    private $processingStatusServiceUri;

    /**
     * @var $awsSignature SignatureV4
     */
    private $awsSignature;

    public function setClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setConfig(array $config)
    {
        if(!isset($config['processing-status']['endpoint'])) {
            throw new RuntimeException("Missing config: ['processing-status']['endpoint']");
        }

        $this->processingStatusServiceUri = $config['processing-status']['endpoint'];
    }

    public function setAwsSignatureV4(SignatureV4 $awsSignature)
    {
        $this->awsSignature = $awsSignature;
    }

    /**
     * @param $ids
     * @return mixed
     * @throws ApiProblemException
     * @throws Exception
     */
    public function getStatuses($ids)
    {
        // build request loop
        $requests = [];
        $successStatus = [];

        $provider = CredentialProvider::defaultProvider();
        $credentials = $provider()->wait();

        foreach ($ids as $id) {

            $prefixedId = $id;

            if (is_numeric($id)) {
                $prefixedId = 'A' . sprintf("%011d", $id);
            }

            $url = new Uri($this->processingStatusServiceUri . $prefixedId);
            $requests[$id] = new Request('GET', $url, $this->buildHeaders());
            $requests[$id] = $this->awsSignature->signRequest($requests[$id], $credentials);
        } //end of request loop

        // build pool
        $results = [];

        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 10,
            'fulfilled' => function ($response, $id) use (&$results) {
                // Each successful response
                $this->getLogger()->debug('We have a result for:' . $id);

                $results[$id] = $response;
                },
            'rejected' => function ($reason, $id){
                $this->getLogger()->debug('Failed to get result for :' . $id .$reason);
            },
        ]);

        // Initiate transfers and create a promise
        $promise = $pool->promise();
        // Force the pool of requests to complete
        $promise->wait();
        // Handle all request response now
        foreach ($results as $lpaId=>$result) {
            $statusCode = $result->getStatusCode();

            switch ($statusCode) {
                case 200:
                    $status = $this->handleResponse($result);
                    $successStatus[$lpaId] = $status;
                    break;

                case 404:
                    // A 404 represents that details for the passed ID could not be found
                    $successStatus[$lpaId] = null;
                    break;

                default:
                    $this->getLogger()
                        ->err('Unexpected response from Sirius gateway: ' . (string)$result->getBody());
                    throw new ApiProblemException('Unexpected response from Sirius gateway: ' . $statusCode);
            } //end switch
        } //end for

        return $successStatus;
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json'
        ];

        return $headers;
    }

    private function handleResponse(ResponseInterface $result)
    {
            $status = json_decode($result->getBody(), true);

            if (is_null($status)){
                return null;
            }

            //  If the body isn't an array now then it wasn't JSON before
            if (!is_array($status)) {
                throw new ApiProblemException($result, 'Malformed JSON response from server');
            }

            if (!$status['status']) {
                return null;
            }
            return self::SIRIUS_STATUS_TO_LPA[$status['status']];
    }
}
