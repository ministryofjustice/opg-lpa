<?php

namespace Application\Controller;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Closure;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient;
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
     * @var Closure
     */
    private $credentialProvider;

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
     * @param Closure $credentialProvider
     * @param ZendDbAdapter $database
     * @param SqsClient $sqsClient
     * @param string $queueUrl
     * @param string $trackMyLpaEndpoint
     * @param HttpClient $httpClient
     */
    public function __construct(
        Closure $credentialProvider,
        ZendDbAdapter $database,
        SqsClient $sqsClient,
        string $queueUrl,
        string $trackMyLpaEndpoint,
        HttpClient $httpClient
    ) {
        $this->credentialProvider = $credentialProvider;
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
        //  Initialise the states as false
        $queueOk    = false;
        $zendDbOk   = false;
        $opgGateway   = false;

        //---

        // Check DynamoDB - initialise the status as false
        $queueDetails = [
            'available' => false,
            'length' => null,
            'lengthAcceptable' => false,
        ];

        //---------------------------------------------
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
        } catch (Exception $ignore) {
        }

        //---------------------------------------------
        // Main database

        try {
            $this->database->getDriver()->getConnection()->connect();

            $zendDbOk = true;
        } catch (Exception $ignore) {
        }

        //---------------------------------------------
        // OPG Gateway

        try {
            $url = new Uri(rtrim($this->trackMyLpaEndpoint, '/') . '/healthcheck');

            $request = new Request('GET', $url, $headers = [
                'Accept' => 'application/json',
                'Content-type'  => 'application/json',
            ]);

            $signer = new SignatureV4('execute-api', 'eu-west-1');

            // Sign the request with an AWS Authorization header.
            $signedRequest = $signer->signRequest($request, $this->credentialProvider()->wait());

            $response = $this->httpClient->sendRequest($signedRequest);

            // Healthcheck should return a 200 code if Sirius gateway is OK
            if ($response->getStatusCode() === 200) {
                $opgGateway = true;
            }
        } catch (Exception $ignore) {
            $this->getLogger()->info(
                "Error returned by Sirius gateway healthcheck at $url: " . $ignore->getMessage()
            );
        }

        //---------------------------------------------

        $result = [
            'database' => [
                'ok' => $zendDbOk,
            ],
            'gateway' => [
                'ok' => $opgGateway,
            ],
            'ok' => ($queueOk && $zendDbOk && $opgGateway),
            'queue' => [
                'details' => $queueDetails,
                'ok' => $queueOk,
            ],
        ];

        $this->getLogger()->info('PingController results', $result);

        return new JsonModel($result);
    }
}
