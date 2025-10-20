<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Aws\Sqs\SqsClient;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\JsonResponse;
use MakeShared\Constants;
use Psr\Http\Client\ClientInterface as HttpClient;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class PingHandler implements RequestHandlerInterface
{
    public function __construct(
        private CredentialsInterface $awsCredentials,
        private SignatureV4 $signer,
        private DbAdapter $database,
        private SqsClient $sqsClient,
        private string $sqsQueueUrl,
        private string $trackMyLpaEndpoint,
        private HttpClient $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Initialise the states as false
        $queueOk = false;
        $dbOk = false;
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
            $this->logger->error('SQS queue is not available to API: ' . $e->getMessage());
        }

        // Main database
        try {
            $this->database->getDriver()->getConnection()->connect();
            $dbOk = true;
        } catch (Exception $e) {
            $this->logger->error('Database is not available to API: ' . $e->getMessage());
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
            $this->logger->error(
                "Sirius gateway not available to API at $url: " . $e->getMessage()
            );
        }

        $status = Constants::STATUS_FAIL;

        $ok = ($queueOk && $dbOk);
        if ($ok) {
            $status = Constants::STATUS_WARN;

            if ($opgGatewayOk) {
                $status = Constants::STATUS_PASS;
            }
        }

        $result = [
            'database' => [
                'ok' => $dbOk,
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

        $this->logger->info('PingController results', $result);

        return new JsonResponse($result);
    }
}
