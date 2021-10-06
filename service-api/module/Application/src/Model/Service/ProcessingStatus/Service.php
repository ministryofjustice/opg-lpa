<?php

namespace Application\Model\Service\ProcessingStatus;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use Aws\Credentials\CredentialsInterface;
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
        'Payment Pending' => Lpa::SIRIUS_PROCESSING_STATUS_RECEIVED,
        'Perfect' => Lpa::SIRIUS_PROCESSING_STATUS_CHECKING,
        'Imperfect' => Lpa::SIRIUS_PROCESSING_STATUS_CHECKING,
        'Invalid' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
        'Rejected' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
        'Withdrawn' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
        'Registered' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
        'Cancelled' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
        'Revoked' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED,
    ];

    /**
     * @var $httpClient HttpClient
     */
    private $httpClient;

    /**
     * @var $credentials CredentialsInterface
     */
    private $credentials;

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

    public function setCredentials(CredentialsInterface $credentials)
    {
        $this->credentials = $credentials;
    }

    public function setConfig(array $config)
    {
        if (!isset($config['processing-status']['endpoint'])) {
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
        $siriusResponseArray = [];

        foreach ($ids as $id) {
            $prefixedId = $id;

            if (is_numeric($id)) {
                $prefixedId = 'A' . sprintf("%011d", $id);
            }

            $url = new Uri($this->processingStatusServiceUri . $prefixedId);
            $requests[$id] = new Request('GET', $url, $this->buildHeaders());
            $requests[$id] = $this->awsSignature->signRequest($requests[$id], $this->credentials);
        } //end of request loop

        // build pool
        $results = [];

        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 50,
            'options' => [
                'http_errors' => false,
            ],
            'fulfilled' => function ($response, $id) use (&$results) {
                // Each successful response
                $this->getLogger()->debug('We have a result for:' . $id);

                $results[$id] = $response;
            },
            'rejected' => function ($reason, $id) {
                $this->getLogger()->debug('Failed to get result for :' . $id . $reason);
            },
        ]);

        // Initiate transfers and create a promise
        $promise = $pool->promise();
        // Force the pool of requests to complete
        $promise->wait();
        // Handle all request response now
        foreach ($results as $lpaId => $result) {
            $statusCode = $result->getStatusCode();

            switch ($statusCode) {
                case 200:
                    $response = $this->handleResponse($result);
                    $siriusResponseArray[$lpaId] = $response;
                    break;

                case 404:
                    // A 404 represents that details for the passed ID could not be found
                    $siriusResponseArray[$lpaId] = null;
                    break;

                default:
                    $this->getLogger()
                        ->err('Unexpected response from Sirius gateway: ' . (string)$result->getBody());
                    throw new ApiProblemException('Unexpected response from Sirius gateway: ' . $statusCode);
            } //end switch
        } //end for

        return $siriusResponseArray;
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
        $responseBody = json_decode($result->getBody(), true);

        if (is_null($responseBody)) {
            return null;
        }

        //  If the body isn't an array now then it wasn't JSON before
        if (!is_array($responseBody)) {
            throw new ApiProblemException($result, 'Malformed JSON response from server');
        }

        $return = [];

        if (isset($responseBody['registrationDate'])) {
            $return['registrationDate'] = $responseBody['registrationDate'];
        }
        if (isset($responseBody['receiptDate'])) {
            $return['receiptDate'] = $responseBody['receiptDate'];
        }
        if (isset($responseBody['rejectedDate'])) {
            $return['rejectedDate'] = $responseBody['rejectedDate'];
        }
        if (isset($responseBody['invalidDate'])) {
            $return['invalidDate'] = $responseBody['invalidDate'];
        }
        if (isset($responseBody['withdrawnDate'])) {
            $return['withdrawnDate'] = $responseBody['withdrawnDate'];
        }
        if (isset($responseBody['dispatchDate'])) {
            $return['dispatchDate'] = $responseBody['dispatchDate'];
        }
        if (isset($responseBody['status'])) {
            $status = self::SIRIUS_STATUS_TO_LPA[$responseBody['status']];

            // We change the status manually to "checking" if the LPA
            // is registered but has no dispatch date set yet; the logic
            // in our front end assumes a "processed" LPA will have a dispatch,
            // rejected, invalid or withdrawn date (registration date is now
            // ignored), so we shouldn't treat a registered LPA without one
            // of these dates as "processed"
            if (!isset($responseBody['dispatchDate']) && $responseBody['status'] === 'Registered') {
                $status = Lpa::SIRIUS_PROCESSING_STATUS_CHECKING;
            }

            // We manually change status to "Processed" if it is "Returned",
            // as service-front wants "Processed" rather than "Returned" (LPAL-92)
            if ($status === Lpa::SIRIUS_PROCESSING_STATUS_RETURNED) {
                $status = 'Processed';
            }

            $return['status'] = $status;
        }

        return $return;
    }
}
