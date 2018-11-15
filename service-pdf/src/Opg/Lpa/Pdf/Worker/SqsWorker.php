<?php
namespace Opg\Lpa\Pdf\Worker;

use Opg\Lpa\Pdf\Config\Config;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException;
use Zend\Filter\Decompress;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

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

            if (count($result->get('Messages')) > 0) {
                $sqsMessage = $result->get('Messages')[0];

                // Get the encrypted data and metadata
                $lpaMessage = json_decode($sqsMessage['Body'], true);

                // Decrypt and decompress the body
                $body = $this->decodeBody($lpaMessage['data']);

                $this->logger->info("New job found on queue", [
                    'jobId' => $lpaMessage['jobId'],
                    'lpaId' => $lpaMessage['lpaId'],
                ]);

                //---

                try {
                    // Generate the PDF
                    $this->run($lpaMessage['jobId'], $body['type'], $body['lpa']);

                } catch (\Exception $e) {
                    $this->logger->err("Error generating PDF", [
                        'jobId' => $lpaMessage['jobId'],
                        'lpaId' => $lpaMessage['lpaId'],
                    ]);

                    throw $e;
                }

                //---

                // If we get here, delete the job from the queue.
                $client->deleteMessage([
                    'QueueUrl' => $sqsUrl,
                    'ReceiptHandle' => $sqsMessage['ReceiptHandle'],
                ]);

            } else {
                echo date('c').": no message found in queue, finishing\n";
            }

        } catch (\Exception $e) {
            $this->logger->emerg("Exception in SqsWorker: ".$e->getMessage());
        }

    }

    private function decodeBody($message){

        $config = Config::getInstance();
        $encryptionConfig = $config['pdf']['encryption'];
        $encryptionKeysQueue = $encryptionConfig['keys']['queue'];

        if (!is_string($encryptionKeysQueue) || strlen($encryptionKeysQueue) != 32) {
            throw new InvalidArgumentException('Invalid encryption key');
        }

        //  We use AES encryption with Cipher-block chaining (CBC).
        $blockCipher = BlockCipher::factory('openssl', $encryptionConfig['options']);
        $blockCipher->setKey($encryptionKeysQueue);

        //  Get the JSON from the message and decompress it
        $decompressFilter = new Decompress('Gz');
        $compressedJson = $blockCipher->decrypt($message);
        $json = $decompressFilter->filter($compressedJson);

        return json_decode($json, true);
    }

}
