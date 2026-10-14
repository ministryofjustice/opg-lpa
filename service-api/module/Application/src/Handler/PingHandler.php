<?php

declare(strict_types=1);

namespace Application\Handler;

use Aws\Sqs\SqsClient;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use MakeShared\Constants;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class PingHandler implements RequestHandlerInterface
{
    public function __construct(
        private DbAdapter $database,
        private SqsClient $sqsClient,
        private string $sqsQueueUrl,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Initialise the states as false
        $queueOk = false;
        $dbOk = false;

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

        $ok = $queueOk && $dbOk;

        $result = [
            'ok' => $ok,
            'status' => $ok ? Constants::STATUS_PASS : Constants::STATUS_FAIL,
            'database' => [
                'ok' => $dbOk,
            ],
            'queue' => [
                'ok' => $queueOk,
                'details' => $queueDetails,
            ],
        ];

        $this->logger->info('PingController results', $result);

        return new JsonResponse($result);
    }
}
