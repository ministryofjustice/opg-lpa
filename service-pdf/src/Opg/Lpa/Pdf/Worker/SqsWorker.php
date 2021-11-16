<?php
namespace Opg\Lpa\Pdf\Worker;

use Opg\Lpa\Pdf\Config\Config;
use Laminas\Filter\Decompress;
use Aws\Sqs\SqsClient;

class SqsWorker extends AbstractWorker
{

    /**
     * Return the object for handling the response
     *
     * @param $docId
     * @return Response\AbstractResponse
     */
    protected function getResponseObject($docId)
    {
        return new Response\S3Response($docId);
    }

    /**
     * @throws \Exception
     */
    public function start()
    {
        $config = Config::getInstance();

        if (!isset($config['queue']['sqs'])) {
            throw new \Exception('SQS not configured');
        }

        $client = new SqsClient($config['queue']['sqs']['client']);

        //---

        if (!isset($config['queue']['sqs']['settings']['url'])) {
            throw new \Exception('SQS URL not configured');
        }

        $sqsUrl = $config['queue']['sqs']['settings']['url'];

        //---

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

                $this->getLogger()->debug('----------------- RETRIEVED SQS MESSAGE ' .
                    $sqsMessage['MessageId'] . ' AT ' . microtime(true) .
                    ' TO GENERATE PDF FOR LPA ' . $lpaId);

                // Decompress the message's body
                $body = (new Decompress('Gz'))->filter(base64_decode($lpaMessage['data']));

                // Decode the returned JSON into an array
                $body = json_decode($body, true);

                $this->getLogger()->info("New job found on queue", [
                    'jobId' => $lpaMessage['jobId'],
                    'lpaId' => $lpaId,
                ]);

                //---

                try {
                    $startTime = microtime(true);

                    // Generate the PDF
                    $this->run($lpaMessage['jobId'], $body['type'], $body['lpa']);

                    $this->getLogger()->info("----------------- DONE - Generation time: ".
                        (microtime(true) - $startTime) .
                        " seconds to make PDF for LPA " . $lpaId);

                } catch (\Exception $e) {
                    $this->getLogger()->err("Error generating PDF", [
                        'jobId' => $lpaMessage['jobId'],
                        'lpaId' => $lpaMessage['lpaId'],
                    ]);

                    throw $e;
                }

                // If we get here, delete the job from the queue.
                $client->deleteMessage([
                    'QueueUrl' => $sqsUrl,
                    'ReceiptHandle' => $sqsMessage['ReceiptHandle'],
                ]);

            } else {
                $this->getLogger()->debug("No message found in queue for this poll, finishing thread.");
            }

        } catch (\Exception $e) {
            $this->getLogger()->emerg("Exception in SqsWorker: ".$e->getMessage());
            sleep(5);
        }

    }

}
