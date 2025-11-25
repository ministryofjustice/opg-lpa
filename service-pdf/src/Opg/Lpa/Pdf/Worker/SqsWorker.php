<?php

namespace Opg\Lpa\Pdf\Worker;

use Aws\Sqs\SqsClient;
use Exception;
use Laminas\Filter\Decompress;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\PdfRenderer;
use Opg\Lpa\Pdf\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class SqsWorker implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var PdfRenderer */
    private PdfRenderer $pdfRenderer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdfRenderer = new PdfRenderer(Config::getInstance());
    }

    /**
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string|array $lpaData JSON document representing the LPA document.
     * @throws Exception
     */
    private function run(string $docId, string $type, string|array $lpaData): void
    {
        $pdf = $this->pdfRenderer->render($docId, $type, $lpaData);
        $pdfFilePath = $pdf['filepath'];

        if (is_null($pdfFilePath)) {
            $this->getLogger()->error('Empty path returned for generated PDF', [
                'docId' => $docId,
                'type' => $type,
            ]);
            return;
        }

        try {
            $response = new Response\S3Response($docId);
            $response->save($pdf['content']);
        } finally {
            unlink($pdfFilePath);
        }
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        $config = Config::getInstance();

        if (!isset($config['queue']['sqs'])) {
            throw new Exception('SQS not configured');
        }

        $client = new SqsClient($config['queue']['sqs']['client']);

        if (!isset($config['queue']['sqs']['settings']['url'])) {
            throw new Exception('SQS URL not configured');
        }

        $sqsUrl = $config['queue']['sqs']['settings']['url'];

        try {
            $result = $client->receiveMessage([
                'QueueUrl' => $sqsUrl,
                'MaxNumberOfMessages' => 1,
                'WaitTimeSeconds' => 20,    // Max value is 20 (seconds)
            ]);

            if ($result->hasKey('Messages') && count($result->get('Messages')) > 0) {
                $sqsMessage = $result->get('Messages')[0];

                // Get the encrypted data and metadata
                $lpaMessage = json_decode($sqsMessage['Body'], true);

                $lpaId = $lpaMessage['lpaId'];
                $this->getLogger()->debug('Retrieved SQS message', [
                    'sqsMessageId' => $sqsMessage['MessageId'],
                    'lpaId' => $lpaId,
                    'jobId' => $lpaMessage['jobId'],
                    'receivedAt' => microtime(true),
                    'sqsUrl' => $sqsUrl
                ]);

                // Decompress the message's body
                $body = (new Decompress('Gz'))->filter(base64_decode($lpaMessage['data']));

                // Decode the returned JSON into an array
                $body = json_decode($body, true);

                $this->getLogger()->info('New job found on queue', [
                    'jobId' => $lpaMessage['jobId'],
                    'lpaId' => $lpaId,
                ]);

                try {
                    $startTime = microtime(true);

                    // Generate the PDF
                    $this->run($lpaMessage['jobId'], $body['type'], $body['lpa']);

                    $this->getLogger()->info('Generated PDF', [
                        'jobId' => $lpaMessage['jobId'],
                        'lpaId' => $lpaId,
                        'generationTimeSeconds' => (microtime(true) - $startTime)
                    ]);
                } catch (Exception $e) {
                    $this->getLogger()->error('Error generating PDF', [
                        'jobId' => $lpaMessage['jobId'],
                        'lpaId' => $lpaMessage['lpaId'],
                        'exception' => $e,
                        'status' => $e->getCode()
                    ]);

                    throw $e;
                }

                // If we get here, delete the job from the queue.
                $client->deleteMessage([
                    'QueueUrl' => $sqsUrl,
                    'ReceiptHandle' => $sqsMessage['ReceiptHandle'],
                ]);
            } else {
                $this->getLogger()->debug('No message found in queue for this poll, finished thread.', [
                    'sqsUrl' => $sqsUrl
                ]);
            }
        } catch (Exception $e) {
            $this->getLogger()->emergency('Exception in SQS Worker', [
                'exception' => $e,
                'status' => $e->getCode(),
                'error_code' => 'SQS_WORKER_EXCEPTION',
            ]);
            sleep(5);
        }
    }
}
