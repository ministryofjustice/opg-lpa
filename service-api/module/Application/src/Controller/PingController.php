<?php

namespace Application\Controller;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Closure;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient;
use MakeShared\Constants;
use MakeShared\Logging\LoggerTrait;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Exception;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Aws\Sqs\SqsClient;

/**
 * Class PingController
 * @package Application\Controller
 */
class PingController extends AbstractRestfulController
{
    use LoggerTrait;

    /**
     * @var CredentialsInterface
     */
    private $awsCredentials;

    /**
     * @var SignatureV4
     */
    private $signer;

    /**
     * @var ZendDbAdapter
     */
    private $database;

    /**
     * @var SqsClient
     */
    private $sqsClient;

    /**
     * @var string
     */
    private $sqsQueueUrl;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $trackMyLpaEndpoint;

    /**
     * PingController constructor.
     * @param CredentialsInterface $awsCredentials
     * @param SignatureV4 $signer
     * @param ZendDbAdapter $database
     * @param SqsClient $sqsClient
     * @param string $queueUrl
     * @param string $trackMyLpaEndpoint
     * @param HttpClient $httpClient
     */
    public function __construct(
        CredentialsInterface $awsCredentials,
        SignatureV4 $signer,
        ZendDbAdapter $database,
        SqsClient $sqsClient,
        string $queueUrl,
        string $trackMyLpaEndpoint,
        HttpClient $httpClient
    ) {
        $this->awsCredentials = $awsCredentials;
        $this->signer = $signer;
        $this->database = $database;
        $this->sqsClient = $sqsClient;
        $this->sqsQueueUrl = $queueUrl;
        $this->trackMyLpaEndpoint = $trackMyLpaEndpoint;
        $this->httpClient = $httpClient;
    }

    /**
     * Endpoint for the AWS ELB.
     * All we're checking is that PHP can be called and a 200 returned.
     *
     * @return \Laminas\Stdlib\ResponseInterface
     */
    public function elbAction()
    {
        $response = $this->getResponse();
        $response->setContent('Happy face');
        return $response;
    }

    /**
     * @return JsonModel
     * @throws \Http\Client\Exception
     */
    public function indexAction()
    {
        // Initialise the states as false
        $queueOk = false;
        $zendDbOk = false;
        $opgGatewayOk = false;

        // Check DynamoDB - initialise the status as false
        $queueDetails = [
            'available' => false,
            'length' => null,
            'lengthAcceptable' => false,
        ];

        // PDF Queue / SQS
        try {
            $result = $this->sqsClient->getQueueAttributes([
                'QueueUrl' => $this->sqsQueueUrl,
                'AttributeNames' => ['ApproximateNumberOfMessages', 'ApproximateNumberOfMessagesNotVisible'],
            ]);

            if (
                !isset($result['Attributes']['ApproximateNumberOfMessages'])
                || !isset($result['Attributes']['ApproximateNumberOfMessagesNotVisible'])
            ) {
                throw new Exception('Invalid count returned');
            }

            $count = (int)$result['Attributes']['ApproximateNumberOfMessages']
                        + (int)$result['Attributes']['ApproximateNumberOfMessagesNotVisible'];

            $queueDetails = [
                'available' => true,
                'length' => $count,
                'lengthAcceptable' => ($count < 50),
            ];

            $queueOk = ($count < 50);
        } catch (Exception $e) {
            $this->getLogger()->err('SQS queue is not available to API: ' . $e->getMessage());
        }

        // Main database
        try {
            $this->database->getDriver()->getConnection()->connect();
            $zendDbOk = true;
        } catch (Exception $e) {
            $this->getLogger()->err('Database is not available to API: ' . $e->getMessage());
        }

        // OPG Gateway
        try {
            $url = new Uri(rtrim($this->trackMyLpaEndpoint, '/') . '/healthcheck');

            $request = new Request('GET', $url, $headers = [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ]);

            // Sign the request with an AWS Authorization header.
            $signedRequest = $this->signer->signRequest($request, $this->awsCredentials);

            $response = $this->httpClient->sendRequest($signedRequest);

            // Healthcheck should return a 200 code if Sirius gateway is OK
            if ($response->getStatusCode() === 200) {
                $opgGatewayOk = true;
            }
        } catch (Exception $e) {
            $this->getLogger()->err(
                "Sirius gateway not available to API at $url: " . $e->getMessage()
            );
        }

        $status = Constants::STATUS_FAIL;

        $ok = ($queueOk && $zendDbOk);
        if ($ok) {
            $status = Constants::STATUS_WARN;

            if ($opgGatewayOk) {
                $status = Constants::STATUS_PASS;
            }
        }

        $result = [
            'database' => [
                'ok' => $zendDbOk,
            ],
            'gateway' => [
                'ok' => $opgGatewayOk,
            ],
            'ok' => $ok,
            'status' => $status,
            'queue' => [
                'details' => $queueDetails,
                'ok' => $queueOk,
            ],
        ];

        $this->getLogger()->info('PingController results', $result);

        return new JsonModel($result);
    }
}
