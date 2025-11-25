<?php

namespace Application\Model\Service\ProcessingStatus;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Model\Service\AbstractService;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception as HttpException;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use GuzzleHttp\Pool;
use GuzzleHttp\Client as HttpClient;
use RuntimeException;

class Service extends AbstractService
{
    use LoggerTrait;

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
        'Return - unpaid' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED
    ];

    /* @var $httpClient HttpClient */
    private $httpClient;

    /* @var $credentials CredentialsInterface */
    private $credentials;

    /* @var $processingStatusServiceUri string */
    private $processingStatusServiceUri;

    /* @var $awsSignature SignatureV4 */
    private $awsSignature;

    /**
     * @param HttpClient $httpClient
     * @return void
     * @psalm-api
     */
    public function setClient(HttpClient $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param CredentialsInterface $credentials
     * @return void
     * @psalm-api
     */
    public function setCredentials(CredentialsInterface $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @param array $config
     * @return void
     * @psalm-api
     */
    public function setConfig(array $config): void
    {
        if (!isset($config['processing-status']['endpoint'])) {
            throw new RuntimeException("Missing config: ['processing-status']['endpoint']");
        }

        $this->processingStatusServiceUri = rtrim($config['processing-status']['endpoint'], '/');
        $this->processingStatusServiceUri .= '/lpa-online-tool/lpas/';
    }

    /**
     * @param SignatureV4 $awsSignature
     * @return void
     * @psalm-api
     */
    public function setAwsSignatureV4(SignatureV4 $awsSignature): void
    {
        $this->awsSignature = $awsSignature;
    }

    /**
     * @param string[] $ids
     *
     * @return mixed
     *
     * @throws ApiProblemException
     * @throws HttpException
     *
     * @psalm-param non-empty-list<string> $ids
     */
    public function getStatuses(array $ids)
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
        }

        // build pool
        $results = [];

        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => 50,
            'options' => [
                'http_errors' => false,
            ],
            'fulfilled' => function ($response, $id) use (&$results) {
                // Each successful response
                $this->getLogger()->debug('We have a get status', [
                    'id' => $id
                ]);
                $results[$id] = $response;
            },
            'rejected' => function ($reason, $id) {
                $this->getLogger()->debug('Failed to get status for LPA application', [
                    'id' => $id ,
                    'reason' => $reason
                ]);
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
                    $responseBodyString = $result->getBody()->getContents();
                    $response = $this->handleResponse($responseBodyString);
                    $siriusResponseArray[$lpaId] = [
                        'deleted'   => false,
                        'response'  => $response
                    ];
                    break;

                case 404:
                    // A 404 represents that details for the passed ID could not be found
                    $siriusResponseArray[$lpaId] = [
                        'deleted'   => false,
                        'response'  => null
                    ];
                    break;

                case 410:
                    // A 410 represents the LPA has recently been deleted from Sirius
                    $siriusResponseArray[$lpaId] = [
                        'deleted'   => true,
                        'response'  => null
                    ];
                    break;

                case 500:
                case 503:
                    $this->getLogger()->error('Bad Response from Sirius Gateway', [
                        'userId' => $this->getUserId(),
                        'error_code' => 'SIRIUS_BAD_RESPONSE',
                        'status' => $statusCode,
                        'exception' => (string)$result->getBody(),
                    ]);

                    throw new ApiProblemException('Bad response from Sirius gateway: ' . $statusCode);

                default:
                    $this->getLogger()->error('Unexpected Response from Sirius Gateway', [
                        'userId' => $this->getUserId(),
                        'error_code' => 'SIRIUS_UNEXPECTED_RESPONSE',
                        'status' => $statusCode,
                        'exception' => (string)$result->getBody(),
                    ]);
                    break;
            }
        }

        return $siriusResponseArray;
    }

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        return [
            'Accept' => 'application/json',
            'Accept-Language' => 'en'
        ];
    }

    /**
     * @return (mixed|null|string|true)[]|null
     *
     * @psalm-return array{registrationDate?: mixed, receiptDate?: mixed, rejectedDate?: mixed, invalidDate?: mixed, withdrawnDate?: mixed, dispatchDate?: mixed|null, returnUnpaid?: true, status?: 'Checking'|'Processed'|'Received'}|null
     */
    private function handleResponse(string $responseBodyString): array|null
    {
        $responseBody = json_decode($responseBodyString, true);

        // Bad JSON from the server, or JSON which doesn't result in an array
        if (is_null($responseBody) || !is_array($responseBody)) {
            return null;
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

            // We set a returnUnpaid as this is required to differentiate from returned
            if ($responseBody['status'] === 'Return - unpaid') {
                // statusDate should always be set, but put a guard on it so we can
                // always have a dispatchDate set to null for the worst case
                $return['dispatchDate'] = null;
                if (isset($responseBody['statusDate'])) {
                    $return['dispatchDate'] = $responseBody['statusDate'];
                }

                $return['returnUnpaid'] = true;
            }

            $return['status'] = $status;
        }

        return $return;
    }
}
