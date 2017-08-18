<?php

namespace Opg\Lpa\Pdf\Worker;

use DynamoQueue\Worker\ProcessorInterface;
use Opg\Lpa\Pdf\Config\Config;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException;
use Zend\Filter\Decompress;

class DynamoQueueWorker extends AbstractWorker implements ProcessorInterface
{
    /**
     * Return the object for handling the response
     *
     * @param $docId
     * @return Response\S3Response
     */
    protected function getResponseObject($docId)
    {
        return new Response\S3Response($docId);
    }

    /**
     * Process the specified job
     *
     * @param $jobId
     * @param $message
     */
    public function perform($jobId, $message)
    {
        $messageSize = strlen($message);

        $this->logger->info("New message: $messageSize bytes\n");

        $config = Config::getInstance();
        $encryptionConfig = $config['pdf']['encryption'];
        $encryptionKeysQueue = $encryptionConfig['keys']['queue'];

        if (!is_string($encryptionKeysQueue) || strlen($encryptionKeysQueue) != 32) {
            throw new InvalidArgumentException('Invalid encryption key');
        }

        //  We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $encryptionConfig['options']);

        //  Set the secret key
        $blockCipher->setKey($encryptionKeysQueue);

        $compressedJson = $blockCipher->decrypt($message);

        //  Decompress the JSON
        $decompressFilter = new Decompress('Gz');
        $json = $decompressFilter->filter($compressedJson);

        $data = json_decode($json, true);

        // Run the job...
        $this->run($jobId, $data['type'], $data['lpa']);
    }
}
