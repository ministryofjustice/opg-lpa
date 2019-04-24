<?php

namespace Application\Controller;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Exception;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Aws\Sqs\SqsClient;

/**
 * Class PingController
 * @package Application\Controller
 */
class PingController extends AbstractRestfulController
{
    use LoggerTrait;

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

    public function __construct(
        ZendDbAdapter $database,
        SqsClient $sqsClient,
        string $queueUrl,
        string $trackMyLpaEndpoint,
        HttpClient $httpClient
    )
    {
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
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function elbAction()
    {
        $response = $this->getResponse();

        // Include a sanity check on ssl certs
        $path = '/etc/ssl/certs/b204d74a.0';

        if (!is_link($path) | !is_readable($path) || !is_link($path) || empty(file_get_contents($path))) {
            $response->setStatusCode(500);
            $response->setContent('Sad face');
        } else {
            $response->setContent('Happy face');
        }

        return $response;
    }

    /**
     * @return JsonModel
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

            if (!isset($result['Attributes']['ApproximateNumberOfMessages'])
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
        } catch (Exception $ignore) {}

        //---------------------------------------------
        // Main database

        try {
            $this->database->getDriver()->getConnection()->connect();
            $zendDbOk = true;

        } catch (Exception $ignore) {}

        //---------------------------------------------
        // OPG Gateway

        try {

            $url = new Uri($this->trackMyLpaEndpoint . 'A00000000000');

            $request = new Request('GET', $url, $headers = [
                'Accept'        => 'application/json',
                'Content-type'  => 'application/json'
            ]);

            $provider = CredentialProvider::defaultProvider();

            $signer = new SignatureV4('execute-api', 'eu-west-1');

            // Sign the request with an AWS Authorization header.
            $signed_request = $signer->signRequest($request, $provider()->wait());

            $response = $this->httpClient->sendRequest($signed_request);

            // We're looking up a non-existing LPA, thus we expect a 404.
            if ($response->getStatusCode() === 404) {
                $opgGateway = true;
            }

        } catch (Exception $ignore) {}

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
